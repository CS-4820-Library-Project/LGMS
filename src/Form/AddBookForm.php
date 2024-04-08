<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Exception;

/**
 * Form handler for adding and editing book entities.
 *
 * Provides a form for adding new book entities to the system or editing existing
 * ones. It supports different types of book entities (print and eBook), manages
 * cover image uploads, and facilitates the dynamic adjustment of form fields
 * based on the book type.
 */
class AddBookForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'add_book_form';
  }

  /**
   * Builds the add/edit book form.
   *
   * @param array $form The initial form array.
   * @param FormStateInterface $form_state The state of the form.
   * @param mixed $ids Optional parameters passed to the form.
   *
   * @return array The form array with all form elements added.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ids = null): array
  {
    $form["#tree"] = TRUE;

    // Set the prefix, suffix, and hidden fields
    $form_helper = new FormHelper();
    $form_helper->set_form_data($form,$ids, $this->getFormId());

    // In the case of editing a Book, get the item
    $current_item = property_exists($ids, 'current_item')? Node::load($ids->current_item) : null;
    $current_book = $current_item?->get('field_book_item')->entity;
    $edit = $current_item != null;

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item Title'),
      '#required' => TRUE,
      '#default_value' => $edit? $current_book->getTitle(): '',
    ];

    $form['author/editor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Author/Editor'),
      '#required' => TRUE,
      '#default_value' => $edit? $current_book->get('field_book_author_or_editor')->value: '',
    ];

    $form['publisher'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publisher'),
      '#required' => TRUE,
      '#default_value' => $edit? $current_book->get('field_book_publisher')->value: '',
    ];

    $form['year'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Year'),
      '#required' => TRUE,
      '#default_value' => $edit? $current_book->get('field_book_year')->value: '',
    ];

    $form['edition'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Edition'),
      '#required' => TRUE,
      '#default_value' => $edit? $current_book->get('field_book_edition')->value: '',
    ];

    $form['cover_picture'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Cover Picture'),
      '#upload_location' => 'public://cover_picture/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
      ],
      '#default_value' => $current_book?->field_book_cover_picture->target_id ? [$current_book->field_book_cover_picture->target_id] : NULL,
      '#required' => FALSE,
      '#description' => $this->t('Allowed extensions: png jpg jpeg'),
    ];

    $form['description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#required' => TRUE,
      '#default_value' => $edit? $current_book->get('field_book_description')->value: '',
      '#format' => $edit ? $current_book->get('field_book_description')->format : 'basic_html',
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Type'),
      '#options' => [
        'print' => $this
          ->t('print'),
        'eBook' => $this
          ->t('eBook'),
      ],
      '#required' => FALSE,
      '#default_value' => $edit? $current_book->get('field_book_type')->value: 'print',
    ];

    $form['call_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Call Number'),
      '#default_value' => $edit? $current_book->get('field_book_call_number')->value: '',
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => 'print'],
        ],
        'required' => [
          ':input[name="type"]' => ['value' => 'print'],
        ],
        'invisible' => [
          ':input[name="type"]' => ['value' => 'eBook'],
        ],
      ],
    ];

    $form['location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Location'),
      '#default_value' => $edit? $current_book->get('field_book_location')->value: '',
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => 'print'],
        ],
        'required' => [
          ':input[name="type"]' => ['value' => 'print'],
        ],
        'invisible' => [
          ':input[name="type"]' => ['value' => 'eBook'],
        ],
      ],
    ];

    $form['cat_record_group'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cat Record'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => 'print'],
        ],
        'invisible' => [
          ':input[name="type"]' => ['value' => 'eBook'],
        ],
      ],
    ];

    $form['cat_record_group']['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Link Text'),
      '#states' => [
        'required' => [
          ':input[name="type"]' => ['value' => 'print'],
        ],
      ],
      '#default_value' => $edit? $current_book->get('field_book_cat_record')->title: '',
    );

    $form['cat_record_group']['url'] = array(
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#default_value' => $edit? $current_book->get('field_book_cat_record')->uri: '',
      '#states' => [
        'required' => [
          ':input[name="type"]' => ['value' => 'print'],
        ],
      ]
    );

    $form['pub_finder_group'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Access Ebook'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => 'eBook'],
        ],
        'invisible' => [
          ':input[name="type"]' => ['value' => 'print'],
        ],
      ],
    ];

    $form['pub_finder_group']['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Link Text'),
      '#states' => [
        'required' => [
          ':input[name="type"]' => ['value' => 'eBook'],
        ],
      ],
      '#default_value' => $edit? $current_book->get('field_book_pub_finder')->title: '',
    );

    $form['pub_finder_group']['url'] = array(
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#default_value' => $edit? $current_book->get('field_book_pub_finder')->uri: '',
      '#states' => [
        'required' => [
          ':input[name="type"]' => ['value' => 'eBook'],
        ],
      ]
    );

    $form['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Draft mode'),
      '#description' => $edit && !$current_book->isPublished() ? $this->t('Please publish the original node') : $this->t('Un-check this box to publish.'),
      '#default_value' => $edit ? $current_item->isPublished() == '0': 0,
      '#disabled' => $edit && !$current_book->isPublished(),
    ];

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

  /**
   * Custom validation for the AddBookForm.
   *
   * Validates specific fields based on the selected book type and other criteria,
   * ensuring required information is provided before submission.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   */
  public function validateFields(array &$form, FormStateInterface $form_state): void
  {
    if($form_state->getValue('type') == 'print'){
      if(empty($form_state->getValue('call_number'))){
        $form_state->setErrorByName('call_number', t('Call Number is required.'));
      }
      if(empty($form_state->getValue('location'))) {
        $form_state->setErrorByName('location', t('Location is required.'));
      }
      if(empty($form_state->getValue(['cat_record_group', 'url']))){
        $form_state->setErrorByName('cat_record_group][url', t('Cat Record\'s url is required.'));
      }
      if(empty($form_state->getValue(['cat_record_group', 'label']))){
        $form_state->setErrorByName('cat_record_group][label', t('Cat Record\'s label is required.'));
      }
    }
    else {
      if(empty($form_state->getValue(['pub_finder_group', 'url']))){
        $form_state->setErrorByName('pub_finder_group][url', t('Pub Finder\'s url is required.'));
      }
      if(empty($form_state->getValue(['pub_finder_group', 'label']))){
        $form_state->setErrorByName('pub_finder_group][label', t('Pub Finder\'s label is required.'));
      }
    }
  }

  /**
   * Handles AJAX form submission for the book form.
   *
   * Provides AJAX support to submit the form, allowing for a smoother user
   * experience without requiring a page reload.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   *
   * @return AjaxResponse The response for the AJAX request.
   *
   * @throws EntityMalformedException If there's an issue with the entity data.
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    $ajaxHelper = new FormHelper();
    $message = 'A Book item has been added.';

    if ($form_state->getValue('current_item')){
      $message = 'A Book item has been edited.';
    }

    return $ajaxHelper->submitModalAjax($form, $form_state, $message, '#'.$this->getFormId());
  }

  /**
   * Handles the form submission.
   *
   * Processes the input from the form, creating or updating a book entity with
   * the provided values. Manages file usage for uploaded images to ensure they
   * are not removed by the system if unused elsewhere.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   *
   * @throws EntityStorageException If there's an issue saving the entity.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $ajaxHelper = new FormHelper();

    $form_field_values = [
      'title' => $form_state->getValue('title'),
      'field_book_author_or_editor' => $form_state->getValue('author/editor'),
      'field_book_publisher' => $form_state->getValue('publisher'),
      'field_book_year' => $form_state->getValue('year'),
      'field_book_edition' => $form_state->getValue('edition'),
      'field_book_description' => [
        'value' => $form_state->getValue('description')['value'],
        'format' => $form_state->getValue('description')['format'],
      ],
      'field_book_cat_record' => [
        'title' => $form_state->getValue(['cat_record_group', 'label']),
        'uri' => $form_state->getValue(['cat_record_group', 'url']),
      ],
      'field_book_pub_finder' => [
        'title' => $form_state->getValue(['pub_finder_group', 'label']),
        'uri' => $form_state->getValue(['pub_finder_group', 'url']),
      ],
      'field_book_type' => $form_state->getValue('type'),
      'field_book_location' => $form_state->getValue('location'),
      'field_book_call_number' => $form_state->getValue('call_number'),
      'status' => $form_state->getValue('published') == '0',
      'promote' => 0,
    ];

    // Create a new book
    if($form_state->getValue('current_item') == null){
      $new_book = Node::create(['type' => 'guide_book_item', ...$form_field_values]);
      $new_book->save();

      $this->handleUserPicture($new_book, $form_state);

      // Create a link to the book and attach it to the box
      $ajaxHelper->create_link($new_book, $form_state->getValue('current_box'));
    }
    else { // Edit a book item
      // Load link and it's content
      $current_item = $form_state->getValue('current_item');
      $current_item = Node::load($current_item);
      $book = $current_item->get('field_book_item')->entity;

      // Update Book content
      foreach ($form_field_values as $key => $value) {
        if ($key == 'status'){
          continue;
        }
        $book->set($key, $value);
      }

      // Update Picture
      $new_picture_fid = $form_state->getValue(['cover_picture', 0]);
      $old_picture_fid = $book->get('field_book_cover_picture')->target_id;

      if(!($new_picture_fid &&  $old_picture_fid == $new_picture_fid)){
        if ($old_picture_fid) {
          $book->set('field_book_cover_picture', NULL);
        }

        if($new_picture_fid){
          $this->handleUserPicture($book, $form_state);
        }
      }

      $book->save();

      // Update link
      $ajaxHelper->update_link($form, $form_state, $current_item);
    }

    // Update last change date for parents.
    $ajaxHelper->updateParent($form, $form_state);
  }

  /**
   * Manages the file usage for an uploaded book cover picture.
   *
   * Ensures the uploaded file is marked as permanent and records its usage,
   * preventing Drupal from automatically removing it as unused.
   *
   * @param EntityInterface $node The book entity to associate the file with.
   * @param FormStateInterface $form_state The state of the form.
   *
   * @throws EntityStorageException If there's an issue saving the file entity.
   */
  protected function handleUserPicture(EntityInterface $node, FormStateInterface $form_state): void {
    $picture_fid = $form_state->getValue(['cover_picture', 0]);

    if (!empty($picture_fid)) {
      $file = File::load($picture_fid);
      if ($file) {
        $file->setPermanent();
        $file->save();
        // Ensure file usage is recorded to prevent the file from being deleted.
        \Drupal::service('file.usage')->add($file, 'lgmsmodule', 'node', $node->id());
        $node->set('field_book_cover_picture', $picture_fid);
        $node->save();
      }
    }
  }
}
