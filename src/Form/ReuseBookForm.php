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
    // Set the prefix, suffix, and hidden fields
    $form_helper = new FormHelper();
    $form_helper->set_form_data($form,$ids, $this->getFormId());

    // Select element for Book items.
    $form['book_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Book Item'),
      '#options' => $this->getBookItemOptions(),
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

    $form['update_wrapper']['prev_node'] = [
      '#type' => 'hidden',
      '#default_value' => NULL
    ];

    // Pre-fill form fields if a Database item is selected.
    $this->prefillSelectedBookItem($form, $form_state);

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
    // Get the selected book
    $selected = $form_state->getValue('book_select');

    if (!empty($selected)) {
      // Load the selected book
      $selected_node = Node::load($selected);

      if ($selected_node) {
        $reference = $form_state->getValue('reference');

        $form['update_wrapper']['reference'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('<Strong>Link:</Strong> By selecting this, a link to the Book item will be created. it will be un-editable from this box'),
        ];

        $form['update_wrapper']['title'] = [
          '#type' => 'textfield',
          '#title' => $this->t('New Title:'),
          '#default_value' => $reference? $this->t('This is just a Link and cannot be edited.') : $selected_node->label(),
          '#required' => !$reference,
          '#disabled' => $reference,
          '#states' => [
            'invisible' => [':input[name="reference"]' => ['checked' => TRUE]],
          ],
        ];

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

        $ebook = 'eBook';
        $print = 'print';

        $form['update_wrapper']['type'] = [
          '#type' => 'select',
          '#title' => $this
            ->t('Type'),
          '#options' => [
            $print => $this
              ->t('print'),
            $ebook => $this
              ->t('eBook'),
          ],
          '#default_value' => $selected_node->get('field_book_type')->value,
          '#states' => [
            'invisible' => [':input[name="reference"]' => ['checked' => TRUE]],
          ],
          '#ajax' => [
            'callback' => '::bookItemSelectedAjaxCallback',
            'wrapper' => 'update-wrapper',
            'event' => 'change',
          ],
        ];

        $type_check = $form_state->getValue('type');

        if (!$form_state->getValue('prev_node') || $form_state->getValue('prev_node') != $selected_node->id()){
          $type_check = $selected_node->get('field_book_type')->value;
        }

        $book_type = $selected_node->get('field_book_type')->value;
        $isEbookTypeSelected = ($book_type === $ebook);
        $isPrintTypeSelected = ($book_type === $print);


        \Drupal::logger('type ###')->notice('Node ID: @id, Type: @type', ['@id' => $type_check, '@type' => gettype($type_check)]);

        if ($type_check == $ebook){
          $form['update_wrapper']['pub_finder_group'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Access Ebook'),
            '#description' => $this->t('Provide the text and URL for the Access Ebook.'),
            '#collapsible' => FALSE,
            '#collapsed' => FALSE,
            '#required' => $isEbookTypeSelected,
            '#states' => [
              'invisible' => [
                [':input[name="type"]' => ['value' => $print]],
                [':input[name="reference"]' => ['checked' => TRUE]],
              ],
            ],
          ];

          $form['update_wrapper']['pub_finder_group']['label2'] = [
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

          $form['update_wrapper']['pub_finder_group']['url2'] = [
            '#type' => 'url',
            '#title' => $this->t('URL'),
            '#description' => $this->t('The URL for the Access Ebook.'),
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
          $form['update_wrapper']['cat_record_group']['label1'] = [
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

          $form['update_wrapper']['cat_record_group']['url1'] = [
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
        $parent_db = $selected_node->get('field_parent_item')->entity;
        $form['update_wrapper']['published'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Draft mode'),
          '#description' => $this->t('Un-check this box to publish.'),
          '#default_value' => $parent_db->isPublished() == '0',
        ];
      }
    }
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function bookItemSelectedAjaxCallback(array &$form, FormStateInterface $form_state) {
    $selected = $form_state->getValue('book_select');
    $selected_node = Node::load($selected);

    if (!$selected_node){
      return $form['update_wrapper'];
    }

    $type_check = $form_state->getValue('type');

    if (!$form_state->getValue('prev_node') || $form_state->getValue('prev_node') != $selected_node->id()){
      $type_check = $selected_node->get('field_book_type')->value;
      $form['update_wrapper']['type']['#value'] = $type_check;
    }

    $ebook = 'eBook';
    $print = 'print';

    if ($selected_node){
      $book_type = $selected_node->get('field_book_type')->value;
      $isEbookTypeSelected = ($book_type === $ebook);

      // changing the values for the user to see them in the form
      $form['update_wrapper']['title']['#value'] = $selected_node->label();
      $form['update_wrapper']['author/editor']['#value'] = $selected_node->get('field_book_author_or_editor')->value;
      $form['update_wrapper']['publisher']['#value'] = $selected_node->get('field_book_publisher')->value;
      $form['update_wrapper']['year']['#value'] = $selected_node->get('field_book_year')->value;
      $form['update_wrapper']['edition']['#value'] = $selected_node->get('field_book_edition')->value;
      $form['update_wrapper']['description']['value']['#value'] = $selected_node->get('field_book_description')->value;
      //$form['update_wrapper']['type']['#value'] = $type_check;
      //$form['update_wrapper']['type1']['#value'] = $type_check;

      if ($type_check){
        if ($type_check == $ebook) {
          $form['update_wrapper']['pub_finder_group']['#required'] = TRUE;
          $form['update_wrapper']['pub_finder_group']['label2']['#required'] = TRUE;
          $form['update_wrapper']['pub_finder_group']['url2']['#required'] = TRUE;

          $form['update_wrapper']['pub_finder_group']['label2']['#value'] = $selected_node->get('field_book_pub_finder')->title;
          $form['update_wrapper']['pub_finder_group']['url2']['#value'] = $selected_node->get('field_book_pub_finder')->uri;

        } else {

          $form['update_wrapper']['call_number']['#value'] = $selected_node->get('field_book_call_number')->value;
          $form['update_wrapper']['location']['#value'] = $selected_node->get('field_book_location')->value;
          $form['update_wrapper']['cat_record_group']['label1']['#value'] = $selected_node->get('field_book_cat_record')->title;
          $form['update_wrapper']['cat_record_group']['url1']['#value'] = $selected_node->get('field_book_cat_record')->uri;

          $form['update_wrapper']['call_number']['#required'] = TRUE;
          $form['update_wrapper']['location']['#required'] = TRUE;
          $form['update_wrapper']['cat_record_group']['#required'] = TRUE;
          $form['update_wrapper']['cat_record_group']['label1']['#required'] = TRUE;
          $form['update_wrapper']['cat_record_group']['url1']['#required'] = TRUE;
        }
      }

      // changing the values for the user to see after submission
      $form_state->setValue('title', $selected_node->label());
      $form_state->setValue('author/editor', $selected_node->get('field_book_author_or_editor')->value);
      $form_state->setValue('publisher', $selected_node->get('field_book_publisher')->value);
      $form_state->setValue('year', $selected_node->get('field_book_year')->value);
      $form_state->setValue('edition', $selected_node->get('field_book_edition')->value);
      $form_state->setValue('description', ['value' => $selected_node->get('field_book_description')->value, 'format' => $selected_node->get('field_book_description')->format]);
      //$form_state->setValue('type1', $selected_node->get('field_book_type')->target_id);
      //$form_state->setValue('type', $selected_node->get('field_book_type')->target_id);

      $form['update_wrapper']['prev_node']['#value'] = $selected_node->id();

    }
    return $form['update_wrapper'];
  }

  public function validateFields(array &$form, FormStateInterface $form_state) {
    $reference = $form_state->getValue('reference');
    $title = $form_state->getValue('title');
    $type_check = $form_state->getValue('type');

    $selected_book = $form_state->getValue('book_select');

    if (empty($selected_book)){
      $form_state->setErrorByName('book_select', $this->t('Please Select A book'));
    } else {
      if (!$reference && empty($title)) {
        $form_state->setErrorByName('title', $this->t('Title: field is required.'));
      }
      if($type_check == 'print'){
        if(empty($form_state->getValue('call_number'))){
          $form_state->setErrorByName('call_number', t('Call Number is required.'));
        }
        if(empty($form_state->getValue('location'))) {
          $form_state->setErrorByName('location', t('Location is required.'));
        }
        if(empty($form_state->getValue('url1'))){
          $form_state->setErrorByName('cat_record_group][url', t('Cat Record\'s url is required.'));
        }
        if(empty($form_state->getValue('label1'))){
          $form_state->setErrorByName('cat_record_group][label', t('Cat Record\'s label is required.'));
        }

      } else {
        if(empty($form_state->getValue('url2'))){
          $form_state->setErrorByName('pub_finder_group][url', t('Access Ebook\'s URL is required.'));
        }
        if(empty($form_state->getValue('label2'))){
          $form_state->setErrorByName('pub_finder_group][label', t('Access Ebook\'s Label is required.'));
        }
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


  /**
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_box_id = $form_state->getValue('current_box');
    $current_box = Node::load($current_box_id);

    $book = Node::load($form_state->getValue('book_select'));
    $item = $book->get('field_parent_item')->entity;

    $type = $form_state->getValue('type');

    if(!$form_state->getValue('reference')){
      $new_book = $book->createDuplicate();
      $new_item = $item->createDuplicate();

      $new_book->set('field_parent_item', $new_item);
      $new_book->set('title', $form_state->getValue('title'));
      $new_book->set('field_book_author_or_editor', $form_state->getValue('author/editor'));
      $new_book->set('field_book_publisher', $form_state->getValue('publisher'));
      $new_book->set('field_book_year', $form_state->getValue('publisher'));
      $new_book->set('field_book_edition', $form_state->getValue('edition'));
      $new_book->set('field_book_description', [
        'value' => $form_state->getValue('description')['value'],
        'format' => $form_state->getValue('description')['format']
      ]);
      $new_book->set('field_book_type', $form_state->getValue('type'));
      if ($type == 'print'){
        $new_book->set('field_book_cat_record', [
          'title' => $form_state->getValue('label1'),
          'uri' => $form_state->getValue('url1')
        ]);
        $new_book->set('field_book_location', $form_state->getValue('location'));
        $new_book->set('field_book_call_number', $form_state->getValue('call_number'));
      }else{
        $new_book->set('field_book_pub_finder', [
          'title' => $form_state->getValue('label2'),
          'uri' => $form_state->getValue('url2')
        ]);
      }


      $new_book->set('status', $form_state->getValue('published') == '0');
      $new_book->save();

      $new_item->set('field_parent_box', $current_box);
      $new_item->set('title', $form_state->getValue('title'));
      $new_item->set('field_book_item', $new_book);

      $new_item->save();

      $item = $new_item;
    } else {
      $new_item = $item->createDuplicate();
      $new_item->set('field_book_item', $book);
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
}

