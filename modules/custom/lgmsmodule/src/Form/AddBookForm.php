<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Exception;

class AddBookForm extends FormBase {

  public function getFormId() {
    return 'add_book_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $ids = null) {
    $form["#tree"] = TRUE;

    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    $form['current_box_content'] = [
      '#type' => 'hidden',
      '#value' => $ids->current_box_content,
    ];

    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $ids->current_node,
    ];

    $current_item = null;
    $edit = false;

    if(property_exists($ids, 'current_item')){
      $current_item = $ids->current_item;
      $form['current_item'] = [
        '#type' => 'hidden',
        '#value' => $current_item,
      ];

      $edit = true;
      $current_item = Node::load($current_item);
      $current_item = $current_item->get('field_book_item')->entity;
    }

    $form['edit'] = [
      '#type' => 'hidden',
      '#value' => $edit,
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item Title'),
      '#required' => TRUE,
      '#default_value' => $edit? $current_item->getTitle(): '',
    ];

    $form['author/editor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Author/Editor'),
      '#required' => TRUE,
      '#default_value' => $edit? $current_item->get('field_book_author_or_editor')->value: '',
    ];

    $form['publisher'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publisher'),
      '#required' => TRUE,
      '#default_value' => $edit? $current_item->get('field_book_publisher')->value: '',
    ];

    $form['year'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Year'),
      '#required' => TRUE,
      '#default_value' => $edit? $current_item->get('field_book_year')->value: '',
    ];

    $form['edition'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Edition'),
      '#required' => TRUE,
      '#default_value' => $edit? $current_item->get('field_book_edition')->value: '',
    ];

    $form['cover_picture'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Cover Picture'),
      '#upload_location' => 'public://cover_picture/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
      ],
      '#default_value' => $current_item->field_book_cover_picture->target_id ? [$current_item->field_book_cover_picture->target_id] : NULL,
      '#required' => FALSE,
      '#description' => $this->t('Allowed extensions: png jpg jpeg'),
    ];

    $form['description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#after_build' => [[get_class($this), 'hideTextFormatHelpText'],],
      '#required' => TRUE,
      '#default_value' => $edit? $current_item->get('field_book_description')->value: '',
      '#format' => $edit ? $current_item->get('field_book_description')->format : 'basic_html',
    ];

    $term_ids = [];

    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
      'name' => ['eBook', 'print'],
      'vid' => 'LGMS_Guide_Book_Type',
    ]);

    if (!empty($terms)) {
      foreach ($terms as $term) {
        $term_ids[$term->label()] = $term->id();
      }
    }

    $form['terms'] = [
      '#type' => 'hidden',
      '#value' => $term_ids,
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Type'),
      '#options' => [
        $term_ids['print'] => $this
          ->t('print'),
        $term_ids['eBook'] => $this
          ->t('eBook'),
      ],
      '#required' => FALSE,
      '#default_value' => $edit? $current_item->get('field_book_type')->target_id: $term_ids['print'],
    ];

    $form['call_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Call Number'),
      '#default_value' => $edit? $current_item->get('field_book_call_number')->value: '',
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => $term_ids['print']],
        ],
        'required' => [
          ':input[name="type"]' => ['value' => $term_ids['print']],
        ],
        'invisible' => [
          ':input[name="type"]' => ['value' => $term_ids['eBook']],
        ],
      ],
    ];

    $form['location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Location'),
      '#default_value' => $edit? $current_item->get('field_book_location')->value: '',
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => $term_ids['print']],
        ],
        'required' => [
          ':input[name="type"]' => ['value' => $term_ids['print']],
        ],
        'invisible' => [
          ':input[name="type"]' => ['value' => $term_ids['eBook']],
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
          ':input[name="type"]' => ['value' => $term_ids['print']],
        ],
        'invisible' => [
          ':input[name="type"]' => ['value' => $term_ids['eBook']],
        ],
      ],
    ];

    $form['cat_record_group']['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Link Text'),
      '#default_value' => $edit? $current_item->get('field_book_cat_record')->title: '',
    );

    $form['cat_record_group']['url'] = array(
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#default_value' => $edit? $current_item->get('field_book_cat_record')->uri: '',
      '#states' => [
        'required' => [
          ':input[name="type"]' => ['value' => $term_ids['print']],
        ],
      ]
    );

    $form['pub_finder_group'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Pub Finder'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => $term_ids['eBook']],
        ],
        'invisible' => [
          ':input[name="type"]' => ['value' => $term_ids['print']],
        ],
      ],
    ];

    $form['pub_finder_group']['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Link Text'),
      '#default_value' => $edit? $current_item->get('field_book_pub_finder')->title: '',
    );

    $form['pub_finder_group']['url'] = array(
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#default_value' => $edit? $current_item->get('field_book_pub_finder')->uri: '',
      '#states' => [
        'required' => [
          ':input[name="type"]' => ['value' => $term_ids['eBook']],
        ],
      ]
    );

    $form['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Draft mode'),
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

  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'an Book item has been added.');
  }

  public function validateFields(array &$form, FormStateInterface $form_state) {
    $terms = $form_state->getValue('terms');
    if($form_state->getValue('type') == $terms['print']){
      if(empty($form_state->getValue('call_number'))){
        $form_state->setErrorByName('call_number', t('Call Number is required.'));
      }
      if(empty($form_state->getValue('location'))) {
        $form_state->setErrorByName('location', t('Location is required.'));
      }
      if(empty($form_state->getValue(['cat_record_group', 'url']))){
        $form_state->setErrorByName('cat_record_group][url', t('Cat Record\'s url is required.'));
      }
    }
    else {
      if(empty($form_state->getValue(['pub_finder_group', 'url']))){
        $form_state->setErrorByName('pub_finder_group][url', t('Pub Finder\'s url is required.'));
      }
    }
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
    $edit = $form_state->getValue('edit');

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
      'field_book_url' => [
        'title' => $form_state->getValue(['url_group', 'label']),
        'uri' => $form_state->getValue(['url_group', 'url']),
      ],
      'field_book_type' => $form_state->getValue('type'),
      'field_book_location' => $form_state->getValue('location'),
      'field_book_call_number' => $form_state->getValue('call_number'),
      'status' => $form_state->getValue('published') == '0',
    ];

    if($edit == '0'){
      $current_box_content = $form_state->getValue('current_box_content');
      $current_box_content = Node::load($current_box_content);

      $new_book = Node::create(['type' => 'guide_book_item', ...$form_field_values]);

      $new_book->save();

      $this->handleUserPicture($new_book, $form_state);

      $new_book->save();

      $new_item = Node::create([
        'type' => 'guide_item',
        'title' => $form_state->getValue('title'),
        'field_book_item' => $new_book,
        'field_parent_box_content' => $current_box_content,
        'status' => $form_state->getValue('published') == '0',
      ]);

      $new_item->save();
      $new_book->set('field_parent_item', $new_item);
      $new_book->save();
      $boxList = $current_box_content->get('field_box_items')->getValue();
      $boxList[] = ['target_id' => $new_item->id()];

      $current_box_content->set('field_box_items', $boxList);
      $current_box_content->save();
    }
    else {
      $current_item = $form_state->getValue('current_item');
      $current_item = Node::load($current_item);

      $book = $current_item->get('field_book_item')->entity;

      foreach ($form_field_values as $key => $value) {
        $book->set($key, $value);
      }

      $new_picture_fid = $form_state->getValue(['cover_picture', 0]);
      $old_picture_fid = $book->get('field_book_cover_picture')->target_id;

      if(!($new_picture_fid &&  $old_picture_fid == $new_picture_fid)){

        if ($old_picture_fid) {
          $book->set('field_book_cover_picture', NULL);
          $file = File::load($old_picture_fid);
          if ($file) {
            $file_usage = \Drupal::service('file.usage');
            $file_usage->delete($file, 'lgmsmodule');
            $file->delete();
          }
        }


        if($new_picture_fid){
          $this->handleUserPicture($book, $form_state);
        }
      }

      $book->save();

      $current_item->set('title', $form_state->getValue('title'));
      $current_item->set('status', $form_state->getValue('published') == '0');
      $current_item->set('changed', \Drupal::time()->getRequestTime());
      $current_item->save();
    }

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }

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
      }
    }
  }
}
