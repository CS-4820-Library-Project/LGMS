<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\menu_test\Access\AccessCheck;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a form for adding or editing database entries.
 *
 * This form allows users to create new database nodes or edit existing ones
 * with fields for title, link, proxy configuration, description, and more.
 * The form dynamically adjusts based on user input, such as showing or hiding
 * fields based on the selected options.
 */
class AddDatabaseForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'add_database_form';
  }

  /**
   * Builds the add/edit database form.
   *
   * @param array $form An associative array containing the structure of the form.
   * @param FormStateInterface $form_state The current state of the form.
   * @param mixed $ids (optional) Identifiers needed for form construction.
   *
   * @return array The form structure as an array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ids = null): array
  {
    // Set the prefix, suffix, and hidden fields
    $form_helper = new FormHelper();
    $form_helper->set_form_data($form,$ids, $this->getFormId());

    // In the case of editing a Database, get the item
    $current_item = property_exists($ids, 'current_item')? Node::load($ids->current_item): null;
    $current_database = $current_item?->get('field_database_item')->entity;
    $edit = $current_item != null;

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Database Title:'),
      '#required' => TRUE,
      '#default_value' => $edit? $current_database->getTitle(): '',
    ];

    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Text:'),
      '#required' => TRUE,
      '#default_value' => $edit? $current_database->get('field_database_link')->title: '',
    ];

    $proxy_prefix = \Drupal::config('lgmsmodule.settings')->get('proxy_prefix');
    $current_value = $current_database?->get('field_database_link')->uri;

    if ($edit && $current_database->get('field_make_proxy')->value){
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
      '#title' => $this->t('Make Proxy'),
      '#description' => $this->t('Check this box if the link should include a proxy prefix.'),
      '#default_value' => $edit? $current_database->get('field_make_proxy')->value: 0,
    ];

    $form['include_body'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include Body'),
      '#default_value' => $edit? !$current_database->get('field_hide_body')->value: 1,
    ];

    $form['field_database_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Database Body'),
      '#description' => $this->t('Enter the body content for the database.'),
      '#default_value' => $edit? $current_database->get('field_database_body')->value: '',
      '#format' => $edit ? $current_database->get('field_database_body')->format : 'basic_html',
      '#states' => [
        'invisible' => [
          ':input[name="include_body"]' => ['checked' => False],
        ],
      ],
    ];

    $form['include_desc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include Description'),
      '#default_value' => $edit? !$current_database->get('field_hide_description')->value: 1,
    ];

    // Description field
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Brief Description'),
      '#default_value' => $edit? $current_database->get('field_description')->value: '',
      '#format' => $edit ? $current_database->get('field_description')->format : 'basic_html',
      '#states' => [
        'invisible' => [
          ':input[name="include_desc"]' => ['checked' => False],
        ],
      ],
    ];

    $form['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Draft mode:'),
      '#description' => $edit && !$current_database->isPublished() ? $this->t('Please publish the original node') : $this->t('Un-check this box to publish.'),
      '#default_value' => $edit ? $current_item->isPublished() == '0': 0,
      '#disabled' => $edit && !$current_database->isPublished(),
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

  /**
   * Custom validation for the database form.
   *
   * Ensures all required fields are filled out correctly, applying specific
   * validations based on user input and form configuration.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The current state of the form.
   */
  public function validateFields(array &$form, FormStateInterface $form_state): void
  {
    $reference = $form_state->getValue('include_desc');
    $title = $form_state->getValue('description');
    if ($reference && empty($title)) {
      $form_state->setErrorByName('description', $this->t('description: field is required.'));
    }
  }

  /**
   * Handles AJAX form submissions.
   *
   * Performs the form submission via AJAX, providing a user-friendly response
   * without requiring a full page reload.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return AjaxResponse The AJAX response object.
   *
   * @throws EntityMalformedException If there's an issue with the form submission.
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    $ajaxHelper = new FormHelper();
    $message = 'A Database item has been added.';

    if ($form_state->getValue('current_item')){
      $message = 'A Database item has been edited.';
    }

    return $ajaxHelper->submitModalAjax($form, $form_state, $message, '#'.$this->getFormId());
  }

  /**
   * Submits the add/edit database form.
   *
   * Processes the submitted form data, creating or updating the database node
   * with the provided values.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The current state of the form.
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $ajaxHelper = new FormHelper();

    if(empty($form_state->getValue('current_item'))){
      // Create the new database
      $new_database = Node::create([
        'type' => 'guide_database_item',
        'title' => $form_state->getValue('title'),
        'field_database_link' => ['uri' => $form_state->getValue('field_database_link'), 'title' => $form_state->getValue('link_text')],
        'field_database_body' =>  $form_state->getValue('field_database_body'),
        'field_make_proxy' => $form_state->getValue('field_make_proxy') != '0',
        'field_hide_body' => $form_state->getValue('include_body') == '0',
        'field_hide_description' => $form_state->getValue('include_desc') == '0',
        'field_description' => $form_state->getValue('description'),
        'status' => $form_state->getValue('published') == '0',
        'promote' => 0,
      ]);
      $new_database->save();

      // Create a link to the book and attach it to the box
      $ajaxHelper->create_link($new_database, $form_state->getValue('current_box'));

    } else {
      // Load link and it's content
      $current_item = $form_state->getValue('current_item');
      $current_item = Node::load($current_item);
      $database = $current_item->get('field_database_item')->entity;

      // Update database content
      $database->set('title', $form_state->getValue('title'));
      $database->set('promote', 0);
      $database->set('field_database_link', ['uri' => $form_state->getValue('field_database_link'), 'title' => $form_state->getValue('link_text')]);
      $database->set('field_hide_body', $form_state->getValue('include_body') == '0');
      $database->set('field_database_body', $form_state->getValue('field_database_body'));
      $database->set('field_make_proxy', $form_state->getValue('field_make_proxy') != '0');
      $database->set('field_hide_description', $form_state->getValue('include_desc') == '0');
      $database->set('field_description', $form_state->getValue('description'));
      $database->save();

      // Update link
      $ajaxHelper->update_link($form, $form_state, $current_item);
    }

    $ajaxHelper->updateParent($form, $form_state);
  }
}
