<?php
namespace Drupal\lgmsmodule\Form;

use Drupal;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DeleteContentItemsForm extends FormBase {

  public function getFormId() {
    return 'delete_html_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_helper = new FormHelper();

    $ids = [
      'current_node' => \Drupal::request()->query->get('current_node'),
      'current_box' => \Drupal::request()->query->get('current_box'),
      'current_item' => \Drupal::request()->query->get('current_item'),
    ];
    // Set the prefix, suffix, and hidden fields
    $form_helper->set_form_fields_from_array($form, $ids, $this->getFormId());

    $current_item = Node::load($ids['current_item']);
    $field_to_delete = '';
    $mediaTitle = '';

    // Get the filled field (this is the one to delete)
    foreach ($form_helper->get_fields() as $field_name) {
      if (!$current_item->get($field_name)->isEmpty()) {
        $field_to_delete = $field_name;
        break;
      }
    }

    if ($field_to_delete) {
      $mediaItem = $current_item->get($field_to_delete)->entity;
      $fileEntity = null;

      if ($mediaItem){
        if ($mediaItem->hasField('field_media_audio_file') && !$mediaItem->get('field_media_audio_file')->isEmpty()) {
          $fileEntity = $mediaItem->get('field_media_audio_file')->entity;

        } elseif ($mediaItem->hasField('field_media_document') && !$mediaItem->get('field_media_document')->isEmpty()){
          $fileEntity = $mediaItem->get('field_media_document')->entity;

        } elseif ($mediaItem->hasField('field_media_image') && !$mediaItem->get('field_media_image')->isEmpty()){
          $fileEntity = $mediaItem->get('field_media_image')->entity;

        } elseif ($mediaItem->hasField('field_media_oembed_video') && !$mediaItem->get('field_media_oembed_video')->isEmpty()){
          $mediaTitle = $mediaItem->get('field_media_oembed_video')->value;

        } elseif ($mediaItem->hasField('field_media_video_file') && !$mediaItem->get('field_media_video_file')->isEmpty()){
          $fileEntity = $mediaItem->get('field_media_video_file')->entity;

        }
      }

      if ($fileEntity) {
        $mediaTitle = $fileEntity->getFilename();
      }
    }

    $form['field_name'] = [
      '#type' => 'hidden',
      '#value' => $field_to_delete,
    ];

    $title = '';
    if ($field_to_delete == 'field_html_item'){
      $title = $this->t('<Strong>Are you sure you want to Delete @item_title?</Strong> Deleting this HTML Item will remove it permanently from the system.', ['@item_title' => $current_item->label()]);
    } elseif ($field_to_delete == 'field_book_item'){
      $title = $this->t('<Strong>Are you sure you want to Delete @item_title?</Strong> Deleting this Book Item will remove it permanently from the system.', ['@item_title' => $current_item->label()]);
    } elseif ($field_to_delete == 'field_database_item'){
      $title = $this->t('<Strong>Are you sure you want to Delete @item_title?</Strong> Deleting this Database Item will remove it permanently from the system.', ['@item_title' => $current_item->label()]);
    } elseif ($field_to_delete == 'field_media_image'){
      $title = $this->t('<Strong>Are you sure you want to Delete @item_title Media Item?</Strong>', ['@item_title' => $mediaTitle]);
    }

    $form['Delete'] = [
      '#type' => 'checkbox',
      '#title' => $title,
      '#required' => True
    ];

    //$form['#validate'][] = '::validateCheckbox';

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

  public function validateCheckbox(array &$form, FormStateInterface $form_state): void
  {
    // Check if the 'Delete' checkbox is not checked.
    if (empty($form_state->getValue('Delete'))) {
      // Set an error on the 'Delete' form element if the checkbox is not checked.

      $form_state->setErrorByName('Delete', t('You must agree to the deletion by checking the checkbox.'));
    }
  }


  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Item was deleted Successfully.', '#'.$this->getFormId());
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_box = Node::load($form_state->getValue('current_box'));
    $current_item = Node::load($form_state->getValue('current_item'));
    $field_name = $form_state->getValue('field_name');

    // remove link to current box
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

      // Go though all items that reference the given node and delete them
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

      $field?->delete();
    }

    $current_item?->delete();

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
