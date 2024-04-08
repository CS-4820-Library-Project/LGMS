<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;

/**
 * Form to reorder the items within a box.
 *
 * This form utilizes a tabledrag interface for users to easily adjust the order of
 * items within a selected box. The new order is then saved, reflecting the updated
 * arrangement in the presentation layer.
 */
class ReOderBoxItemsForm extends FormBase {

  /**
   * Checks if the user can edit their own article.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Drupal\Core\Session\AccountInterface $account
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(Request $request, AccountInterface $account) {
    $nid = $request->query->get('current_box');
    $node = Node::load($nid);

    if ($node && $node->getType() == 'guide_box' && $node->access('update')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 're_order_box_items_form';
  }

  /**
   * Builds the reorder box items form.
   *
   * Constructs the form elements necessary for displaying the items in a box
   * and allows for their order to be changed via a drag-and-drop interface.
   *
   * @param array $form The initial form array.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return array The modified form structure with reorder capabilities.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form_helper = new FormHelper();

    // Get the data from the URL
    $ids = (object) [
      'current_node' => \Drupal::request()->query->get('current_node'),
      'current_box' => \Drupal::request()->query->get('current_box'),
    ];

    // Set the prefix, suffix, and hidden fields
    $form_helper->set_form_data($form, $ids, $this->getFormId());

    // Get the items to reorder
    $box = Node::load($ids->current_box);
    $box_items = $box->get('field_box_items');

    // Add reorder items Field
    $form_helper->get_reorder_table($form, $box_items);


    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::submitAjax',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * AJAX callback for form submission.
   *
   * Provides immediate feedback through AJAX upon successful reordering of box items,
   * enhancing user experience by avoiding full page reloads.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return AjaxResponse An AJAX response indicating success.
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box Items Have been re-ordered.', '#'.$this->getFormId());
  }

  /**
   * Processes the submission of the reorder box items form.
   *
   * Applies the new order of items as specified by the user in the form to the actual
   * box entity, ensuring the updated order is reflected site-wide.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $ajaxHelper = new FormHelper();

    // Get the new order
    $values = $form_state->getValue('pages_table');

    // Load the box that holds the items to be sorted
    $current_box = Node::load($form_state->getValue('current_box'));

    // Get the items to sort
    $items = $current_box->get('field_box_items')->getValue();

    // Get the new order
    $items = $ajaxHelper->get_new_order($values,$items);

    // Save the new order
    $current_box->set('field_box_items', array_values($items));
    $current_box->save();

    // Update parents
    $ajaxHelper->updateParent($form, $form_state);
  }
}
