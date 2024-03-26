<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\menu_test\Access\AccessCheck;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

class AddDatabaseForm extends FormBase {

  public function getFormId() {
    return 'add_database_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $ids = null) {
    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    $current_box = $ids->current_box;
    $form['current_box'] = [
      '#type' => 'hidden',
      '#value' => $current_box,
    ];

    $current_node = $ids->current_node;
    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $current_node,
    ];

    $current_item = Node::load($ids->current_item);
    $form['current_item'] = [
      '#type' => 'hidden',
      '#value' => $current_item?->id(),
    ];

    $database = $current_item?->get('field_database_item')->entity;
    $edit = $current_item != null;

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Database Title:'),
      '#required' => TRUE,
      '#default_value' => $edit? $database->getTitle(): '',
    ];

    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Text:'),
      '#required' => TRUE,
      '#default_value' => $edit? $database->get('field_database_link')->title: '',
    ];

    $proxy_prefix = \Drupal::config('lgmsmodule.settings')->get('proxy_prefix');
    $current_value = $database?->get('field_database_link')->uri;

    if ($edit && $database->get('field_make_proxy')->value){
      $current_value = substr($current_value, strlen($proxy_prefix));
    }

    $form['field_database_link'] = [
      '#type' => 'url',
      '#title' => $this->t('Database Link'),
      '#description' => $this->t('Enter the URL of the content without the proxy prefix.'),
      '#required' => TRUE,
      '#default_value' => $edit? $current_value: '',
    ];

    $form['field_make_proxy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include Proxy'),
      '#description' => $this->t('Check this box if the link should include a proxy prefix.'),
      '#default_value' => $edit? $database->get('field_make_proxy')->value: 0,
    ];

    $form['field_database_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Database Body'),
      '#description' => $this->t('Enter the body content for the database.'),
      '#default_value' => $edit? $database->get('field_database_body')->value: '',
      '#format' => $edit ? $database->get('field_database_body')->format : 'basic_html',
    ];

    $form['include_desc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include Description'),
      '#default_value' => !$edit || !empty($current_item->get('field_description')->value),
    ];


    // Body field
    $form['description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#default_value' => $edit? $current_item->get('field_description')->value: '',
      '#format' => $edit ? $current_item->get('field_description')->format : 'basic_html',
      '#states' => [
        'invisible' => [
          ':input[name="include_desc"]' => ['checked' => False],
        ],
      ],
    ];

    $vocabulary = 'LGMS_Guide_Subject';
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary);

// Prepare an options array
    $options = [];
    foreach ($terms as $term) {
      // Use term ID as key and term name as value
      $options[$term->tid] = $term->name;
    }

    $default_values = [];
    if ($edit && $database->hasField('field_db_guide_subject') && !$database->get('field_db_guide_subject')->isEmpty()) {
      foreach ($database->get('field_db_guide_subject')->getValue() as $item) {
        $default_values[$item['target_id']] = $item['target_id'];
      }
    }


    $form['field_db_guide_subject'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Guide Subject'),
      '#options' => $options,
      '#default_value' => $default_values,
      '#description' => $this->t('Select the subject related to this database.'),
      '#required' => FALSE,
    ];

    $form['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Draft mode:'),
      '#description' => $this->t('Un-check this box to publish.'),
      '#default_value' => $edit ? $current_item->isPublished() == '0': 0,
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

    if(empty($form_state->getValue('current_item'))){
      $current_box = $form_state->getValue('current_box');
      $current_box = Node::load($current_box);

      $selected_term_ids = array_filter($form_state->getValue('field_db_guide_subject'), function($value) {
        return $value !== 0;
      });

      $database = Node::create([
        'type' => 'guide_database_item',
        'title' => $form_state->getValue('title'),
        'field_database_link' => ['uri' => $form_state->getValue('field_database_link'), 'title' => $form_state->getValue('link_text')],
        'field_database_body' => $form_state->getValue('field_database_body'),
        'field_db_guide_subject' => array_values($selected_term_ids),
        'field_make_proxy' => $form_state->getValue('field_make_proxy') != '0',
        'status' => $form_state->getValue('published') == '0',
      ]);

      $new_item = Node::create([
        'type' => 'guide_item',
        'title' => $database->label(),
        'field_database_item' => $database,
        'field_parent_box' => $current_box,
        'field_description' => $form_state->getValue('include_desc') == '0'? '': $form_state->getValue('description'),
        'status' => $form_state->getValue('published') == '0',
      ]);

      $new_item->save();

      $database->set('field_parent_item', $new_item);
      $database->save();

      $boxList = $current_box->get('field_box_items')->getValue();
      $boxList[] = ['target_id' => $new_item->id()];

      $current_box->set('field_box_items', $boxList);
      $current_box->save();
    } else {
      \Drupal::logger('my_module')->notice('<pre>' . print_r('here!!!!!!!', TRUE) . '</pre>');
      $current_item = $form_state->getValue('current_item');
      $current_item = Node::load($current_item);

      $database = $current_item->get('field_database_item')->entity;

      $selected_term_ids = array_filter($form_state->getValue('field_db_guide_subject'), function($value) {
        return $value !== 0;
      });

      $database->set('title', $form_state->getValue('title'));
      $database->set('field_database_link', ['uri' => $form_state->getValue('field_database_link'), 'title' => $form_state->getValue('link_text')]);
      $database->set('field_database_body', $form_state->getValue('field_database_body'));
      $database->set('field_db_guide_subject', array_values($selected_term_ids));
      $database->set('field_make_proxy', $form_state->getValue('field_make_proxy') != '0');
      $database->save();

      $current_item->set('title', $database->label());
      $current_item->set('status', $form_state->getValue('published') == '0');
      $current_item->set('field_description', $form_state->getValue('include_desc') == '0'? '': $form_state->getValue('description'));
      $current_item->set('changed', \Drupal::time()->getRequestTime());
      $current_item->save();
    }
    \Drupal::logger('my_module')->notice('<pre>' . print_r('here2!!!!!!!', TRUE) . '</pre>');

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
