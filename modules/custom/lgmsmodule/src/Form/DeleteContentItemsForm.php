<?php
namespace Drupal\lgmsmodule\Form;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a form for deleting content items.
 *
 * This form enables users to delete various types of content items from the
 * system, with a confirmation step to prevent accidental deletions. It is
 * capable of handling HTML items, Book items, and Database items, adjusting
 * its behavior based on the item type to ensure proper deletion of both the
 * item and any associated links or references.
 */
class DeleteContentItemsForm extends FormBase {

  /**
   * Checks if the user can edit their own article.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Drupal\Core\Session\AccountInterface $account
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(Request $request, AccountInterface $account) {
    $nid = $request->query->get('current_item');
    $node = Node::load($nid);

    if ($node && $node->access('delete')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'delete_html_form';
  }

  /**
   * Builds the deletion confirmation form.
   *
   * @param array $form An associative array containing the initial structure of the form.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return array The modified form structure, including the confirmation checkbox and deletion button.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form_helper = new FormHelper();

    $ids = (object) [
      'current_node' => \Drupal::request()->query->get('current_node'),
      'current_box' => \Drupal::request()->query->get('current_box'),
      'current_item' => \Drupal::request()->query->get('current_item'),
    ];

    // Set the prefix, suffix, and hidden fields
    $form_helper->set_form_data($form, $ids, $this->getFormId());

    $current_item = property_exists($ids, 'current_item')? Node::load($ids->current_item) : null;

    // Get the filled field (this is the one to delete)
    $field_to_delete = $form_helper->get_filled_field($current_item);

    $form['field_name'] = [
      '#type' => 'hidden',
      '#value' => $field_to_delete,
    ];

    $link = (
      $current_item->get('field_parent_box')->entity->id() == $ids->current_box
      && $current_item->get('field_lgms_reference')->value
      ) || $current_item->get('field_lgms_reference')->value;

    $message = '<Strong>Are you sure you want to Delete ' . $current_item->label() . '?</Strong>';

    if (!$link){
      if ($field_to_delete == 'field_html_item'){
        $title = $this->t($message . ' Deleting this HTML Item will remove it permanently from the system.');
      } elseif ($field_to_delete == 'field_book_item'){
        $title = $this->t($message . ' Deleting this Book Item will remove it permanently from the system.');
      } elseif ($field_to_delete == 'field_database_item'){
        $title = $this->t($message . ' Deleting this Database Item will remove it permanently from the system.');
      } else {
        $title = $message;
      }
    } else {
      $title = $message;
    }

    $form['Delete'] = [
      '#type' => 'checkbox',
      '#title' => $title,
      '#required' => True
    ];

    $form['#validate'][] = '::validateCheckbox';

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::submitAjax',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * Validates the confirmation checkbox.
   *
   * Ensures the user has checked the confirmation checkbox to proceed with deletion,
   * preventing accidental deletions of content.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   */
  public function validateCheckbox(array &$form, FormStateInterface $form_state): void
  {
    // Check if the 'Delete' checkbox is not checked.
    if (empty($form_state->getValue('Delete'))) {
      // Set an error on the 'Delete' form element if the checkbox is not checked.

      $form_state->setErrorByName('Delete', t('You must agree to the deletion by checking the checkbox.'));
    }
  }


  /**
   * Handles AJAX submissions for the deletion form.
   *
   * Provides a smoother user experience by processing form submissions via AJAX,
   * allowing for immediate feedback without a full page reload.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return AjaxResponse An AJAX response to update the client-side application state.
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): Drupal\Core\Ajax\AjaxResponse
  {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Item was deleted Successfully.', '#'.$this->getFormId());
  }

  /**
   * Processes the form submission for content item deletion.
   *
   * Executes the deletion of the specified content item, including any necessary
   * cleanup of associated entities or references. This method ensures the item is
   * properly removed from the system.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    // Get the field to delete and it's box and item
    $current_box = Node::load($form_state->getValue('current_box'));
    $current_item = Node::load($form_state->getValue('current_item'));
    $field_name = $form_state->getValue('field_name');

    // remove link from the current box
    $child_items = $current_box->get('field_box_items')->getValue();
    $child_items = array_filter($child_items, function ($item) use ($current_item) {
      return $item['target_id'] != $current_item->id();
    });

    $current_box->set('field_box_items', $child_items);
    $current_box->save();

    $field = $current_item->get($field_name)->entity;

    // If this is the box the original item was created in
    if($field->hasField('field_parent_item') && $current_item->id() == $field->get('field_parent_item')->entity->id()){
      // Get all guide_item that point to this field
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'guide_item')
        ->condition($field_name, $field->id())
        ->accessCheck(false);
      $result = $query->execute();

      // Go through all items that reference the given node and delete them
      foreach ($result as $item){
        $item = Node::load($item);
        $parent_box = $item->get('field_parent_box')->entity;

        // remove link to current box
        $child_items = $parent_box->get('field_box_items')->getValue();
        $child_items = array_filter($child_items, function ($box) use ($item) {
          return $box['target_id'] != $item->id();
        });

        $parent_box->set('field_box_items', $child_items);
        $parent_box->save();

        $item?->delete();
      }

      // Delete Field
      $field?->delete();
    }

    // Delete Link
    $current_item?->delete();

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
