<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

class ReuseBookForm extends FormBase {

  public function getFormId() {
    return 'reuse_book_item_form';
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
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
    $options = $this->getBookItemOptions();

    // Select element for HTML items.
    $form['box'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Book Item'),
      '#options' => $options,
      '#empty_option' => $this->t('- Select a Book Item -'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::bookItemSelectedAjaxCallback',
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
    $this->prefillSelectedBookItem($form, $form_state);
    $this->bookItemSelectedAjaxCallback($form,$form_state);

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
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  private function prefillSelectedBookItem(array &$form, FormStateInterface $form_state): void
  {
    $selected = $form_state->getValue('box');

    if (!empty($selected)) {
      $selected_node = Node::load($selected);
      if ($selected_node) {
        $reference = $form_state->getValue('reference');
        $book_type = Term::load($selected_node->get('field_book_type')->target_id)?->label();
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
          '#title' => $this->t('<Strong>Link:</Strong> By selecting this, a link to the Book item will be created. it will be un-editable from this box'),
          '#ajax' => [
            'callback' => '::bookItemSelectedAjaxCallback',
            'wrapper' => 'update-wrapper',
            'event' => 'change',
          ],
        ];

        $reference = $form_state->getValue('reference');

        $form['update_wrapper']['author/editor'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Author/Editor'),
          '#default_value' => $selected_node->get('field_book_author_or_editor')->value,
          '#states' => [
            'invisible' => [':input[name="reference"]' => ['checked' => TRUE]],
          ],
          '#required' => !$reference,
        ];

        $form['update_wrapper']['publisher'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Publisher'),
          '#default_value' => $selected_node->get('field_book_publisher')->value,
          '#states' => [
            'invisible' => [':input[name="reference"]' => ['checked' => TRUE]],
          ],
          '#required' => !$reference,
        ];

        $form['update_wrapper']['year'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Year'),
          '#default_value' => $selected_node->get('field_book_year')->value,
          '#states' => [
            'invisible' => [':input[name="reference"]' => ['checked' => TRUE]],
          ],
          '#required' => !$reference,
        ];

        $form['update_wrapper']['edition'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Edition'),
          '#default_value' => $selected_node->get('field_book_edition')->value,
          '#states' => [
            'invisible' => [':input[name="reference"]' => ['checked' => TRUE]],
          ],
          '#required' => !$reference,
        ];

        if (!$reference){
          $form['update_wrapper']['cover_picture'] = [
            '#type' => 'managed_file',
            '#title' => $this->t('Cover Picture'),
            '#upload_location' => 'public://cover_picture/',
            '#upload_validators' => [
              'file_validate_extensions' => ['png jpg jpeg'],
            ],
            '#default_value' => $selected_node->field_book_cover_picture->target_id ? [$selected_node->field_book_cover_picture->target_id] : NULL,
            '#description' => $this->t('Allowed extensions: png jpg jpeg'),
            '#required' => !$reference,
          ];
        }

        $form['update_wrapper']['description'] = [
          '#type' => 'text_format',
          '#title' => $this->t('Description'),
          '#default_value' => $selected_node->get('field_book_description')->value,
          '#format' => $selected_node->get('field_book_description')->format,
          '#states' => [
            'invisible' => [':input[name="reference"]' => ['checked' => TRUE]],
          ],
          '#required' => !$reference,
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

        $form['update_wrapper']['terms'] = [
          '#type' => 'hidden',
          '#value' => $term_ids,
        ];

        $form['update_wrapper']['type'] = [
          '#type' => 'select',
          '#title' => $this
            ->t('Type'),
          '#options' => [
            'print' => $this
              ->t('print'),
            'eBook' => $this
              ->t('eBook'),
          ],
          '#default_value' => Term::load($selected_node->get('field_book_type')->target_id)?->label(),
          '#states' => [
            'invisible' => [':input[name="reference"]' => ['checked' => TRUE]],
          ],
          '#required' => !$reference,
          '#ajax' => [
            'callback' => '::bookItemSelectedAjaxCallback',
            'wrapper' => 'update-wrapper',
            'event' => 'change',
          ],
        ];


        $type_check = $form_state->getValue('type');
        $ebook = 'eBook';
        $print = 'print';
        $isEbookTypeSelected = ($book_type === $ebook);
        $isPrintTypeSelected = ($book_type === $print);

        if (empty($type_check) && $isEbookTypeSelected){
          $type_check = $ebook;
        } else if (empty($type_check) && $isPrintTypeSelected){
          $type_check = $print;
        }

        if ($type_check === $ebook){
          $form['update_wrapper']['pub_finder_group'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Publication Finder'),
            '#description' => $this->t('Provide the text and URL for the publication finder.'),
            '#collapsible' => FALSE,
            '#collapsed' => FALSE,
            '#states' => [
              'invisible' => [
                [':input[name="type"]' => ['value' => $print]],
                [':input[name="reference"]' => ['checked' => TRUE]],
              ],
            ],
          ];

          $form['update_wrapper']['pub_finder_group']['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Link Text'),
            '#description' => $this->t('The text that will be displayed as the link.'),
            '#default_value' => $selected_node->get('field_book_pub_finder')->title,
            '#required' => $isEbookTypeSelected,
            '#states' => [
              'invisible' => [
                [':input[name="type"]' => ['value' => $print]],
                [':input[name="reference"]' => ['checked' => TRUE]],
              ],
            ],
          ];

          $form['update_wrapper']['pub_finder_group']['url'] = [
            '#type' => 'url',
            '#title' => $this->t('URL'),
            '#description' => $this->t('The URL for the publication finder.'),
            '#default_value' => $selected_node->get('field_book_pub_finder')->uri,
            '#required' => $isEbookTypeSelected,
            '#states' => [
              'invisible' => [
                [':input[name="type"]' => ['value' => $print]],
                [':input[name="reference"]' => ['checked' => TRUE]],
              ],
            ]
          ];
        } else {
          $form['update_wrapper']['call_number'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Call Number'),
            '#description' => $this->t('The library call number for the book.'),
            '#default_value' => $selected_node->get('field_book_call_number')->value,
            '#states' => [
              'invisible' => [
                [':input[name="type"]' => ['value' => $ebook]],
                [':input[name="reference"]' => ['checked' => TRUE]]
              ],
            ],
            '#required' => $isPrintTypeSelected,
          ];

          $form['update_wrapper']['location'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Location'),
            '#description' => $this->t('The physical location of the book in the library.'),
            '#default_value' => $selected_node->get('field_book_location')->value,
            '#states' => [
              'invisible' => [
                [':input[name="type"]' => ['value' => $ebook]],
                [':input[name="reference"]' => ['checked' => TRUE]],
              ],
            ],
            '#required' => $isPrintTypeSelected,
          ];


          $form['update_wrapper']['cat_record_group'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Catalog Record'),
            '#description' => $this->t('Information for the catalog record.'),
            '#collapsible' => FALSE,
            '#collapsed' => FALSE,
            '#states' => [
              'invisible' => [
                [':input[name="type"]' => ['value' => $ebook]],
                [':input[name="reference"]' => ['checked' => TRUE]],
              ],
            ],
            '#required' => $isPrintTypeSelected,
          ];

          // Add descriptive texts to the label and URL fields inside the 'Cat Record' group.
          $form['update_wrapper']['cat_record_group']['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Catalog Link Text'),
            '#description' => $this->t('The text for the link to the catalog record.'),
            '#default_value' => $selected_node->get('field_book_cat_record')->title,
            '#states' => [
              'invisible' => [
                [':input[name="type"]' => ['value' => $ebook]],
                [':input[name="reference"]' => ['checked' => TRUE]],
              ],
            ],
            '#required' => $isPrintTypeSelected,
          ];

          $form['update_wrapper']['cat_record_group']['url'] = [
            '#type' => 'url',
            '#title' => $this->t('Catalog URL'),
            '#description' => $this->t('The URL to the catalog record.'),
            '#default_value' => $selected_node->get('field_book_cat_record')->uri,
            '#states' => [
              'invisible' => [
                [':input[name="type"]' => ['value' => $ebook]],
                [':input[name="reference"]' => ['checked' => TRUE]],
              ],
            ],
            '#required' => $isPrintTypeSelected,
          ];
        }

        $form['update_wrapper']['published'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Draft mode'),
          '#description' => $selected_node->isPublished() ? $this->t('Please publish the original node') : $this->t('Un-check this box to publish.'),
          '#default_value' => $selected_node->isPublished() == '0',
          '#disabled' => $selected_node->isPublished(),
        ];
      }
    }
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function bookItemSelectedAjaxCallback(array &$form, FormStateInterface $form_state) {
    $type_check = $form_state->getValue('type');
    $selected = $form_state->getValue('box');
    $selected_node = Node::load($selected);
    $ebook = 'eBook';
    $print = 'print';
    if ($selected_node){
      $book_type = Term::load($selected_node->get('field_book_type')->target_id)?->label();
      $isEbookTypeSelected = ($book_type === $ebook);
      if ($type_check){
        if ($type_check === $ebook) {
          $form['update_wrapper']['pub_finder_group']['label']['#value'] = $selected_node->get('field_book_pub_finder')->title;
          $form['update_wrapper']['pub_finder_group']['url']['#value'] = $selected_node->get('field_book_pub_finder')->uri;
        } else {
          $form['update_wrapper']['call_number']['#value'] = $selected_node->get('field_book_call_number')->value;
          $form['update_wrapper']['location']['#value'] = $selected_node->get('field_book_location')->value;
          $form['update_wrapper']['cat_record_group']['label']['#value'] = $selected_node->get('field_book_cat_record')->title;
          $form['update_wrapper']['cat_record_group']['url']['#value'] = $selected_node->get('field_book_cat_record')->uri;
        }
      }
      $form['update_wrapper']['title']['#value'] = $selected_node->label();
      $form['update_wrapper']['author/editor']['#value'] = $selected_node->get('field_book_author_or_editor')->value;
      $form['update_wrapper']['publisher']['#value'] = $selected_node->get('field_book_publisher')->value;
      $form['update_wrapper']['year']['#value'] = $selected_node->get('field_book_year')->value;
      $form['update_wrapper']['edition']['#value'] = $selected_node->get('field_book_edition')->value;
      $form['update_wrapper']['description']['value']['#value'] = $selected_node->get('field_book_description')->value;
    }


    return $form['update_wrapper'];
  }

  public function validateFields(array &$form, FormStateInterface $form_state) {
    $reference = $form_state->getValue('reference');
    $title = $form_state->getValue('title');

    if (!$reference && empty($title)) {
      $form_state->setErrorByName('title', $this->t('Title: field is required.'));
    }
    if($form_state->getValue('type') != '912'){
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

    } else {
      if(empty($form_state->getValue(['pub_finder_group', 'url']))){
        $form_state->setErrorByName('pub_finder_group][url', t('Pub Finder\'s URL is required.'));
      }
      if(empty($form_state->getValue(['pub_finder_group', 'label']))){
        $form_state->setErrorByName('pub_finder_group][label', t('Pub Finder\'s Label is required.'));
      }
    }

  }

  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Book created successfully.', '#'.$this->getFormId());
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
    $current_box_id = $form_state->getValue('current_box');
    $current_box = Node::load($current_box_id);


    $book = Node::load($form_state->getValue('box'));
    $item = $book->get('field_parent_item')->entity;

    if(!$form_state->getValue('reference')){

      $new_book = $book->createDuplicate();
      $new_item = $item->createDuplicate();

      $new_book->set('field_parent_item', $new_item);
      $new_book->set('field_book_author_or_editor', $form_state->getValue('author/editor'));
      $new_book->set('field_book_publisher', $form_state->getValue('publisher'));
      $new_book->set('field_book_year', $form_state->getValue('publisher'));
      $new_book->set('field_book_edition', $form_state->getValue('edition'));
      $new_book->set('field_book_description', [
        'value' => $form_state->getValue('description')['value'],
        'format' => $form_state->getValue('description')['format']
      ]);
      $new_book->set('field_book_cat_record', [
        'title' => $form_state->getValue('cat_record_group')['label'],
        'uri' => $form_state->getValue('cat_record_group')['url']
      ]);
      $new_book->set('field_book_pub_finder', [
        'title' => $form_state->getValue('pub_finder_group')['label'],
        'uri' => $form_state->getValue('pub_finder_group')['url']
      ]);
      $new_book->set('field_book_type', $form_state->getValue('type'));
      $new_book->set('field_book_location', $form_state->getValue('location'));
      $new_book->set('field_book_call_number', $form_state->getValue('call_number'));
      $new_book->set('status', $form_state->getValue('published') == '0');

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
      $new_book->set('title', $form_state->getValue('title'));
      $new_book->save();

      $new_item->set('field_parent_box', $current_box);
      $new_item->set('title', $form_state->getValue('title'));
      $new_item->set('field_book_item', $new_book);

      $new_item->save();

      $item = $new_item;
    } else {
      $new_item = $item->createDuplicate();
      $new_item->set('field_book_item', TRUE);
      $new_item->save();
      $item = $new_item;
    }

    $boxList = $current_box->get('field_box_items')->getValue();
    $boxList[] = ['target_id' => $item->id()];

    $current_box->set('field_box_items', $boxList);
    $current_box->save();

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }

  private function getBookItemOptions(): array
  {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'guide_book_item')
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

