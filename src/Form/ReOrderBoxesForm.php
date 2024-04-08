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
 * Form for reordering boxes within a guide or page.
 *
 * This form allows users to adjust the order of content boxes within a guide or page,
 * facilitating a flexible arrangement of content to improve the reader's experience.
 * It utilizes a drag-and-drop interface for intuitive interaction.
 */
class ReOrderBoxesForm extends FormBase {

  /**
   * Checks if the user can edit their own article.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Drupal\Core\Session\AccountInterface $account
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(Request $request, AccountInterface $account) {
    $nid = $request->query->get('current_node');
    $node = Node::load($nid);

    if ($node && ($node->getType() == 'guide' || $node->getType() == 'guide_page') && $node->access('update')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 're_order_guide_box_form';
  }

  /**
   * Builds the reorder boxes form.
   *
   * @param array $form The initial form structure.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return array The form array with elements for reordering boxes.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form_helper = new FormHelper();

    // Get the data from the URL
    $ids = (object) [
      'current_node' => \Drupal::request()->query->get('current_node'),
    ];

    // Set the prefix, suffix, and hidden fields
    $form_helper->set_form_data($form, $ids, $this->getFormId());

    $current_node = Node::load($ids->current_node);
    $child_boxes = $current_node->get('field_child_boxes');

    // Add reorder boxes Field
    $form_helper->get_reorder_table($form, $child_boxes);


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
   * AJAX callback for the reorder form submission.
   *
   * Facilitates a smoother user experience by using AJAX to submit the reorder operation,
   * avoiding the need for a full page reload and providing immediate feedback to the user.
   *
   * @param array &$form The form structure.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return AjaxResponse An AJAX response for the submission.
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'The Boxes have been re-ordered.', '#'.$this->getFormId());
  }

  /**
   * Handles the form submission.
   *
   * Processes the new order of boxes as determined by the user and saves this order
   * back to the database, ensuring the display order matches the user's preference.
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

    // Load the current node that holds the items to be sorted
    $current_node = Node::load($form_state->getValue('current_node'));

    // Get the boxes to be sorted
    $child_boxes = $current_node->get('field_child_boxes')->getValue();

    // Get the new order
    $child_boxes = $ajaxHelper->get_new_order($values,$child_boxes);

    // Save the new order
    $current_node->set('field_child_boxes', array_values($child_boxes));
    $current_node->save();

    // Update parents
    $ajaxHelper->updateParent($form, $form_state);
  }
}
