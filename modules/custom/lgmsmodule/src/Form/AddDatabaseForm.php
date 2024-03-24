<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class AddDatabaseForm extends FormBase {

  public function getFormId() {
    return 'add_database_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    $current_box = \Drupal::request()->query->get('current_box');
    $form['current_box'] = [
      '#type' => 'hidden',
      '#value' => $current_box,
    ];

    $current_node = \Drupal::request()->query->get('current_node');
    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $current_node,
    ];

    $current_item = \Drupal::request()->query->get('current_item');
    $database = '';
    $edit = false;

    if(!empty($current_item)){
      $form['current_item'] = [
        '#type' => 'hidden',
        '#value' => $current_item,
      ];

      $edit = true;
      $current_item = Node::load($current_item);
      $database = $current_item->get('field_database_item')->entity;
    }


    $form['edit'] = [
      '#type' => 'hidden',
      '#value' => $edit,
    ];

    $form['database'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Database Title'),
      '#target_type' => 'node', // Adjust according to your needs
      '#selection_settings' => [
        'target_bundles' => ['guide_database_item'], // Adjust to your guide page bundle
      ],
      '#required' => TRUE,
      '#default_value' => $edit? $database: '',
    ];

    $form['include_desc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include Description'),
      '#default_value' => !$edit || !empty($current_item->get('field_description')->value),
    ];


    // Body field
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Body'),
      '#after_build' => [[get_class($this), 'hideTextFormatHelpText'],],
      '#default_value' => $edit? $current_item->get('field_description')->value: '',
      '#format' => $edit ? $current_item->get('field_description')->format : 'basic_html',
      '#states' => [
        'invisible' => [
          ':input[name="include_desc"]' => ['checked' => False],
        ],
      ],
    ];

    $form['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Draft mode:'),
      '#description' => $this->t('Un-check this box to publish.'),
      '#default_value' => $edit ? $current_item->isPublished() == '0': 0,
    ];

    $form['#validate'][] = '::validateFields';


    $form['actions']['#type'] = 'actions';
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
    $reference = $form_state->getValue('include_desc');
    $title = $form_state->getValue('description');
    if ($reference && empty($title)) {
      $form_state->setErrorByName('description', $this->t('description: field is required.'));
    }
  }

  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'an HTML field has been added.');
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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $edit = $form_state->getValue('edit');

    if($edit == '0'){
      $current_box = $form_state->getValue('current_box');
      $current_box = Node::load($current_box);

      $database = $form_state->getValue('database');
      $database = Node::load($database);

      $new_item = Node::create([
        'type' => 'guide_item',
        'title' => $database->label(),
        'field_database_item' => $database,
        'field_parent_box' => $current_box,
        'field_description' => $form_state->getValue('include_desc') == '0'? '': $form_state->getValue('description'),
        'status' => $form_state->getValue('published') == '0',
      ]);

      $new_item->save();

      $boxList = $current_box->get('field_box_items')->getValue();
      $boxList[] = ['target_id' => $new_item->id()];

      $current_box->set('field_box_items', $boxList);
      $current_box->save();
    } else {
      $current_item = $form_state->getValue('current_item');
      $current_item = Node::load($current_item);

      $database = $form_state->getValue('database');
      $database = Node::load($database);


      $current_item->set('title', $database->label());
      $current_item->set('field_database_item', $database);
      $current_item->set('status', $form_state->getValue('published') == '0');
      $current_item->set('field_description', $form_state->getValue('include_desc') == '0'? '': $form_state->getValue('description'));
      $current_item->set('changed', \Drupal::time()->getRequestTime());
      $current_item->save();
    }

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
