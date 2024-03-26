<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class ReuseDatabaseForm extends FormBase {

  public function getFormId() {
    return 'reuse_database_item_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $ids = null) {
    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    $form['current_box'] = [
      '#type' => 'hidden',
      '#value' => $ids->current_box,
    ];


    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $ids->current_node,
    ];


    $form['box'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Database Item Title'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['guide_database_item'],
      ],
      '#required' => TRUE,
    ];

    $form['reference'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<Strong>Link:</Strong> By selecting this, a link to the HTML item will be created. it will be un-editable from this box'),
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('New Title:'),
      '#states' => [
        'invisible' => [
          ':input[name="reference"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['#validate'][] = '::validateFields';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    $form['actions']['submit']['#ajax'] = [
      'callback' => '::submitAjax',
      'event' => 'click',
    ];

    return $form;
  }

  public function validateFields(array &$form, FormStateInterface $form_state) {
    $reference = $form_state->getValue('reference');
    $title = $form_state->getValue('title');
    if (!$reference && empty($title)) {
      $form_state->setErrorByName('title', $this->t('Title: field is required.'));
    }
  }

  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Database created successfully.');
  }


  public static function hideTextFormatHelpText(array $element, FormStateInterface $form_state) {
    if (isset($element['format']['help'])) {
      $element['format']['help']['#access'] = FALSE;
    }
    if (isset($element['format']['guidelines'])) {
      $element['format']['guidelines']['#access'] = FALSE;
    }
    if (isset($element['format']['#attributes']['class'])) {
      unset($element['format']['#attributes']['class']);
    }
    return $element;
  }

  /**
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Attempt to load the 'current_box'
    $current_box_id = $form_state->getValue('current_box');
    $current_box = Node::load($current_box_id);

    // Attempt to load the 'book'
    $database_id = $form_state->getValue('box');
    $database = Node::load($database_id);
    $item = $database->get('field_parent_item')->entity;
    \Drupal::logger('lgmsmodule')->notice('item ID: @id.', ['@id' => $item->id()]);

    // Check if 'reference' checkbox is checked
    if (!$form_state->getValue('reference')) {

      $new_database = $database->createDuplicate();
      $new_item = $database->get('field_parent_item')->entity->createDuplicate();

      // Update fields on the new book
      $new_database->set('field_parent_item', $new_item);
      $new_database->set('title', $form_state->getValue('title'));
      $new_database->save();

      // Update fields on the new item
      $new_item->set('field_parent_box', $current_box);
      $new_item->set('title', $form_state->getValue('title'));
      $new_item->set('field_database_item', $new_database);
      $new_item->save(); // Saving the new item

      $item = $new_item; // Update $item to refer to the new item
    }

    // Updating the box list with the new item
    $boxList = $current_box->get('field_box_items')->getValue();
    $boxList[] = ['target_id' => $item->id()];

    $current_box->set('field_box_items', $boxList);
    $current_box->save(); // Saving the updated box

    // Assuming the existence of a helper for AJAX updates (replace with actual implementation)
    // $ajaxHelper = new FormHelper();
    // $ajaxHelper->updateParent($form, $form_state);

  }

}

