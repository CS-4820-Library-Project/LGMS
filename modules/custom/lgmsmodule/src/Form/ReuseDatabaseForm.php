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
    $form['#prefix'] = '<div id="' . $this->getFormId() . '">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    // Hidden fields to store current context.
    $form['current_box'] = [
      '#type' => 'hidden',
      '#value' => $ids->current_box,
    ];
    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $ids->current_node,
    ];

    // Load HTML items to populate the select options.
    $options = $this->getDatabaseItemOptions();

    // Select element for HTML items.
    $form['box'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Database Item'),
      '#options' => $options,
      '#empty_option' => $this->t('- Select a Database Item -'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::databaseItemSelectedAjaxCallback',
        'wrapper' => 'update-wrapper',
        'event' => 'change',
      ],
    ];

    // Container to dynamically update based on AJAX callback.
    $form['update_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'update-wrapper'],
    ];

    // Pre-fill form fields if a Database item is selected.
    $this->prefillSelectedDatabaseItem($form, $form_state);

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

  private function prefillSelectedDatabaseItem(array &$form, FormStateInterface $form_state): void
  {
    $selected = $form_state->getValue('box');
    if (!empty($selected)) {
      $selected_node = Node::load($selected);
      if ($selected_node) {
        \Drupal::logger('Im first 1')->notice('<pre>' . print_r('hello 1', TRUE) . '</pre>');
        $parent_db = $selected_node->get('field_parent_item')->entity;
        $reference = $form_state->getValue('reference');

        $form['update_wrapper']['title'] = [
          '#type' => 'textfield',
          '#title' => $this->t('New Title:'),
          '#default_value' => $reference ? $this->t('This is just a reference and cannot be edited.') : $selected_node->label(),
          '#required' => !$reference,
          '#disabled' => $reference,
          '#states' => [
            'invisible' => [':input[name="reference"]' => ['checked' => TRUE]],
          ],
        ];

        $form['update_wrapper']['reference'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('<Strong>Link:</Strong> By selecting this, a link to the HTML item will be created. it will be un-editable from this box'),
        ];

        $form['update_wrapper']['link_text'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Link Text:'),
          '#required' => TRUE,
          '#default_value' => $selected_node->get('field_database_link')->title,
          '#states' => [
            'invisible' => [':input[name="reference"]' => ['checked' => TRUE]],
            'required' => [':input[name="reference"]' => ['checked' => FALSE]],
          ],
        ];

        $proxy_prefix = \Drupal::config('lgmsmodule.settings')->get('proxy_prefix');
        $current_value = $selected_node?->get('field_database_link')->uri;

        if ($selected_node->get('field_make_proxy')->value){
          $current_value = substr($current_value, strlen($proxy_prefix));
        }

        $form['update_wrapper']['field_database_link'] = [
          '#type' => 'url',
          '#title' => $this->t('Database Link'),
          '#description' => $this->t('Enter the URL of the content without the proxy prefix.'),
          '#required' => TRUE,
          '#default_value' => $current_value,
          '#states' => [
            'invisible' => [':input[name="reference"]' => ['checked' => TRUE]],
            'required' => [':input[name="reference"]' => ['checked' => FALSE]],
          ],
        ];

        $form['update_wrapper']['field_make_proxy'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Include Proxy'),
          '#description' => $this->t('Check this box if the link should include a proxy prefix.'),
          '#default_value' => $selected_node->get('field_make_proxy')->value,
          '#states' => [
            'invisible' => [':input[name="reference"]' => ['checked' => TRUE]],
          ],
        ];

        $form['update_wrapper']['includedesc'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Include Description'),
          '#default_value' => !empty($selected_node->get('field_description')->value),
          '#states' => [
            'invisible' => [':input[name="reference"]' => ['checked' => TRUE]],
          ],
        ];

        $form['update_wrapper']['description'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Brief Description'),
          '#default_value' => $selected_node->get('field_description')->value,
          '#format' => $selected_node->get('field_description')->format,
          '#states' => [
            'invisible' => [
              [':input[name="reference"]' => ['checked' => TRUE]],
              [':input[name="includedesc"]' => ['checked' => FALSE]],
            ],
          ],
        ];

        $form['update_wrapper']['includebody'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Include Body'),
          '#default_value' => !empty($selected_node->get('field_database_body')->value),
          '#states' => [
            'invisible' => [
              ':input[name="reference"]' => ['checked' => TRUE],
            ],
          ],
        ];

        $form['update_wrapper']['field_database_body'] = [
          '#type' => 'text_format',
          '#title' => $this->t('Database Body'),
          '#description' => $this->t('Enter the body content for the database.'),
          '#default_value' => $selected_node->get('field_database_body')->value,
          '#format' => $selected_node->get('field_database_body')->format,
          '#states' => [
            'invisible' => [
              [':input[name="reference"]' => ['checked' => TRUE]],
              [':input[name="includebody"]' => ['checked' => FALSE]]
            ],
          ],
        ];

        $form['update_wrapper']['published'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Draft mode:'),
          '#description' => $this->t('Un-check this box to publish.'),
          '#default_value' => $parent_db->isPublished() == '0',
        ];
      }
    }
  }

  public function validateFields(array &$form, FormStateInterface $form_state): void
  {
    $reference = $form_state->getValue('reference');
    $title = $form_state->getValue('title');
    if (!$reference && empty($title)) {
      $form_state->setErrorByName('title', $this->t('Title: field is required.'));
    }
  }

  public function databaseItemSelectedAjaxCallback(array &$form, FormStateInterface $form_state) {
    \Drupal::logger('Im first 2')->notice('<pre>' . print_r('hello 2', TRUE) . '</pre>');
    $selected = $form_state->getValue('box');
    $selected_node = Node::load($selected);
    $proxy_prefix = \Drupal::config('lgmsmodule.settings')->get('proxy_prefix');
    $current_value = $selected_node?->get('field_database_link')->uri;

    if ($selected_node->get('field_make_proxy')->value){
      $current_value = substr($current_value, strlen($proxy_prefix));
    }
    if ($selected_node){
      $parent_db = $selected_node->get('field_parent_item')->entity;
      $form['update_wrapper']['title']['#value'] = $selected_node->label();
      $form['update_wrapper']['link_text']['#value'] = $selected_node->get('field_database_link')->title;
      $form['update_wrapper']['field_database_link']['#value'] = $current_value;
      $form['update_wrapper']['field_make_proxy']['#value'] = $selected_node->get('field_make_proxy')->value;
      $form['update_wrapper']['includedesc']['#checked'] = !empty($selected_node->get('field_description')->value);
      $form['update_wrapper']['description']['#value'] = $selected_node->get('field_description')->value;
      $form['update_wrapper']['includebody']['#checked'] = !empty($selected_node->get('field_database_body')->value);
      $form['update_wrapper']['field_database_body']['value']['#value'] = $selected_node->get('field_database_body')->value;
      $form['update_wrapper']['published']['#checked'] = $parent_db->isPublished() == '0';
    }

    $form_state->setValue('desired_text_format', 'restricted_html');

    return $form['update_wrapper'];
  }

  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): \Drupal\Core\Ajax\AjaxResponse
  {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Database created successfully.', '#'.$this->getFormId());
  }

  /**
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {

    // Attempt to load the 'current_box'
    $current_box_id = $form_state->getValue('current_box');
    $current_box = Node::load($current_box_id);

    // Attempt to load the 'database'
    $database_id = $form_state->getValue('box');
    $database = Node::load($database_id);
    $item = $database->get('field_parent_item')->entity;

    // Check if 'reference' checkbox is checked
    if (!$form_state->getValue('reference')) {

      $new_database = $database->createDuplicate();
      $new_item = $item->createDuplicate();

      // Update fields on the new database
      $new_database->set('field_parent_item', $new_item);
      $new_database->set('title', $form_state->getValue('title'));
      $new_database->set('field_database_link', ['uri' => $form_state->getValue('field_database_link'), 'title' => $form_state->getValue('link_text')]);
      $new_database->set('field_hide_body', $form_state->getValue('includebody') == '0');
      $new_database->set('field_database_body', $form_state->getValue('field_database_body'));
      $new_database->set('field_make_proxy', $form_state->getValue('field_make_proxy') != '0');
      $new_database->set('status',$form_state->getValue('published') == '0');
      $new_database->set('field_hide_description', $form_state->getValue('includedesc') == '0');
      $new_database->set('field_description', $form_state->getValue('description'));
      $new_database->save();

      // Update fields on the new item
      $new_item->set('field_parent_box', $current_box);
      $new_item->set('title', $form_state->getValue('title'));
      $new_item->set('field_database_item', $new_database);
      $new_item->set('status', $form_state->getValue('published') == '0');
      $new_item->save(); // Saving the new item

      $item = $new_item; // Update $item to refer to the new item
    } else {
      $new_item = $item->createDuplicate();
      $new_item->set('field_lgms_database_link', TRUE);
      $new_item->save();
      $item = $new_item;
    }

    // Updating the box list with the new item
    $boxList = $current_box->get('field_box_items')->getValue();
    $boxList[] = ['target_id' => $item->id()];

    $current_box->set('field_box_items', $boxList);
    $current_box->save(); // Saving the updated box

  }

  /**
   * Queries and returns options for the HTML item select field.
   *
   * @return array
   *   An associative array of options for the select field.
   */
  private function getDatabaseItemOptions(): array
  {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'guide_database_item')
      ->sort('title', 'ASC')
      ->accessCheck(TRUE);
    $nids = $query->execute();

    $nodes = Node::loadMultiple($nids);
    $options = [];
    foreach ($nodes as $node) {
      $options[$node->id()] = $node->label();
    }

    return $options;
  }

}

