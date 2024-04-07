<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class ReuseDatabaseForm extends FormBase {

  public function getFormId(): string
  {
    return 'reuse_database_item_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $ids = null): array
  {
    // Set the prefix, suffix, and hidden fields
    $form_helper = new FormHelper();
    $form_helper->set_form_data($form,$ids, $this->getFormId());

    // Select field for database items.
    $form['db_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Database Item'),
      '#options' => $form_helper->get_item_options('guide_database_item'),
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
      '#ajax' => [
        'callback' => '::submitAjax',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  private function prefillSelectedDatabaseItem(array &$form, FormStateInterface $form_state): void
  {
    $selected = $form_state->getValue('db_select');

    if (!empty($selected)) {
      $selected_node = Node::load($selected);

      if ($selected_node) {
        // Get the parent of a database and check if the user wants the link to be a reference
        $parent_db = $selected_node->get('field_parent_item')->entity;
        $reference = $form_state->getValue('reference');

        $form['update_wrapper']['title'] = [
          '#type' => 'textfield',
          '#title' => $this->t('New Title:'),
          '#default_value' => $reference ? $this->t('This is just a Link and cannot be edited.') : $selected_node->label(),
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

        // if it's a proxy link, do not show the proxy in the url
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
    // Load the selected database
    $selected = $form_state->getValue('db_select');
    $selected_node = Node::load($selected);

    // Get the proxy
    $proxy_prefix = \Drupal::config('lgmsmodule.settings')->get('proxy_prefix');
    $current_value = $selected_node?->get('field_database_link')->uri;

    // Remove the proxy
    if ($selected_node->get('field_make_proxy')->value){
      $current_value = substr($current_value, strlen($proxy_prefix));
    }

    // Update the fields' values based on the selected database
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
    $ajaxHelper = new FormHelper();

    // Attempt to load the 'current_box'
    $current_box_id = $form_state->getValue('current_box');
    $current_box = Node::load($current_box_id);

    // Attempt to load the 'database'
    $database_id = $form_state->getValue('db_select');
    $database = Node::load($database_id);
    $item = $database->get('field_parent_item')->entity;

    // Check if 'reference' checkbox is checked
    if (!$form_state->getValue('reference')) {
      // Create copies of the database and link
      $new_database = $database->createDuplicate();
      $new_item = $item->createDuplicate();

      // Update fields on the new database
      $new_database->set('title', $form_state->getValue('title'));
      $new_database->set('field_parent_item', $new_item);
      $new_database->set('field_database_link', ['uri' => $form_state->getValue('field_database_link'), 'title' => $form_state->getValue('link_text')]);
      $new_database->set('field_hide_body', $form_state->getValue('includebody') == '0');
      $new_database->set('field_database_body', $form_state->getValue('field_database_body'));
      $new_database->set('field_make_proxy', $form_state->getValue('field_make_proxy') != '0');
      $new_database->set('status',$form_state->getValue('published') == '0');
      $new_database->set('field_hide_description', $form_state->getValue('includedesc') == '0');
      $new_database->set('field_description', $form_state->getValue('description'));
      $new_database->setOwnerId(\Drupal::currentUser()->id());
      $new_database->save();

      // Update fields on the new item
      $new_item->set('field_parent_box', $current_box);
      $new_item->set('title', $form_state->getValue('title'));
      $new_item->set('field_database_item', $new_database);
      $new_item->set('status', $form_state->getValue('published') == '0');
      $new_item->setOwnerId(\Drupal::currentUser()->id());
      $new_item->save();

      $item = $new_item; // Update $item to refer to the new item
    } else {
      // Create a reference
      $new_item = $item->createDuplicate();
      $new_item->set('field_lgms_database_link', TRUE);
      $new_item->setOwnerId(\Drupal::currentUser()->id());
      $new_item->save();
      $item = $new_item;
    }

    // Updating the box list with the new item
    $ajaxHelper->add_child($current_box, $item, 'field_box_items');

    // Update the parents
    $ajaxHelper->updateParent($form, $form_state);
  }
}

