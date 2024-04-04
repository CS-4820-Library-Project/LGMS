<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class ReuseGuideBoxForm extends FormBase {

  public function getFormId() {
    return 'reuse_guide_box_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $ids = null) {
    $form['#prefix'] = '<div id="' . $this->getFormId() . '">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $ids->current_node,
    ];

    $form['#attributes']['id'] = 'form-selector';

    // Hidden fields to store current context.
    $form['current_box'] = [
      '#type' => 'hidden',
      '#value' => $ids->current_box,
    ];

    // Load HTML items to populate the select options.
    $options = $this->getBoxItemOptions();

    $form['box'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a Box'),
      '#options' => $options,
      '#empty_option' => $this->t('- Select a Box Item -'),
      '#target_type' => 'node', // Adjust according to your needs
      '#selection_settings' => [
        'target_bundles' => ['guide_box'], // Adjust to your guide page bundle
      ],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::boxItemSelectedAjaxCallback',
        'wrapper' => 'update-wrapper',
        'event' => 'change',
      ],
    ];

    $form['reference'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<Strong>Reference:</Strong> By selecting this, a reference of the box will be created. it will be un-editable from this guide/page'),
    ];

    // Container to dynamically update based on AJAX callback.
    $form['update_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'update-wrapper'],
    ];

    // Pre-fill form fields if an HTML item is selected.
    $this->prefillSelectedBoxItem($form, $form_state);

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
    $curr_node = $form_state->getValue('current_node');
    $curr_node = Node::load($curr_node);
    $nid = $curr_node->id();
    $bundle = $curr_node->bundle();
    $box = Node::load($form_state->getValue('box'));
    $box_parent = $box->get('field_parent_node')->target_id;

    if (!$reference && empty($title)) {
      $form_state->setErrorByName('title', $this->t('Box Title: field is required.'));
    }

    if ($reference && $nid == $box_parent){
      if ($bundle == 'guide'){
        $form_state->setErrorByName('reference', $this->t('This box cannot be created with the same guide as its reference. Please select a different guide or remove the reference to proceed.'));
      }else {
        $form_state->setErrorByName('reference', $this->t('This box cannot be created with the same page as its reference. Please select a different page or remove the reference to proceed.'));
      }
    }
  }

  private function prefillSelectedBoxItem(array &$form, FormStateInterface $form_state) {
    $selected = $form_state->getValue('box');

    if (!empty($selected)) {
      $selected_node = Node::load($selected);
      if ($selected_node) {
        $reference = $form_state->getValue('reference');
        $form['update_wrapper']['title'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Box Title:'),
          '#default_value' => $selected_node->label(),
          '#states' => [
            'invisible' => [
              ':input[name="reference"]' => ['checked' => TRUE],
            ],
            'required' => [':input[name="reference"]' => ['checked' => FALSE]],
          ],
        ];
      }
    }
  }

  public function boxItemSelectedAjaxCallback(array &$form, FormStateInterface $form_state) {

    $selected = $form_state->getValue('box');
    $selected_node = Node::load($selected);
    if ($selected_node){
      $form['update_wrapper']['title']['#value'] = $selected_node->label();
    }

    return $form['update_wrapper'];
  }

  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box created successfully.', '#'.$this->getFormId());
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
    $curr_node = $form_state->getValue('current_node');
    $curr_node = Node::load($curr_node);

    $nid = $curr_node->id();

    $box = Node::load($form_state->getValue('box'));
    $box_parent = $box->get('field_parent_node')->target_id;

    if(!$form_state->getValue('reference') && $box_parent !== $nid){
      $new_box = $box->createDuplicate();
      $new_box->set('field_parent_node', $nid);
      $new_box->set('title', $form_state->getValue('title'));
      $new_box->setOwnerId(\Drupal::currentUser()->id());
      $new_box->save();

      $items = $box->get('field_box_items')->referencedEntities();

      $new_items_list = [];
      foreach ($items as $item){
        $new_item = $item->createDuplicate();
        $new_item->set('field_parent_box', $new_box);
        $new_item->setOwnerId(\Drupal::currentUser()->id());

        if ($item->hasField('field_html_item') && !$item->get('field_html_item')->isEmpty()) {
          $html = $item->get('field_html_item')->entity;
          $html = $html->createDuplicate();
          $html->setOwnerId(\Drupal::currentUser()->id());
          $html->save();

          $new_item->set('field_html_item', $html);

        } elseif ($item->hasField('field_database_item') && !$item->get('field_database_item')->isEmpty()) {
          $database = $item->get('field_database_item')->entity;
          $new_item->set('field_database_item', $database);

        } elseif ($item->hasField('field_book_item') && !$item->get('field_book_item')->isEmpty()) {
          $book = $item->get('field_book_item')->entity;
          $book = $book->createDuplicate();
          $book->setOwnerId(\Drupal::currentUser()->id());
          $book->save();

          $new_item->set('field_book_item', $book);
        } elseif ($item->hasField('field_media_image') && !$item->get('field_media_image')->isEmpty()) {
          $media = $item->get('field_media_image')->entity;
          $new_item->set('field_media_image', $media);
        }

        $new_item->save();
        $new_items_list[] = $new_item;
      }

      $new_box->set('field_box_items', $new_items_list);
      $new_box->save();

      $box = $new_box;
    }

    $page = Node::load($nid);
    $boxList = $page->get('field_child_boxes')->getValue();
    $boxList[] = ['target_id' => $box->id()];

    $page->set('field_child_boxes', $boxList);
    $page->save();

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }

  /**
   * Queries and returns options for the Box item select field.
   *
   * @return array
   *   An associative array of options for the select field.
   */
  private function getBoxItemOptions() {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'guide_box')
      ->sort('title', 'ASC')
      ->accessCheck(TRUE);
    $nids = $query->execute();

    $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
    $groupedOptions = [];
    foreach ($nodes as $node) {
      // Load the direct parent item.
      $parent_item = $node->get('field_parent_node')->entity;

      // Initialize parent label.
      $parent_label = (string) t('No Parent');

      if ($parent_item && $parent_item->access('view')) {
        // Determine if the parent item is a 'guide_page'.
        if ($parent_item->bundle() === 'guide_page') {
          // If the parent is a 'guide_page', find the 'guide' it belongs to.
          $ultimate_parent = $parent_item->get('field_parent_guide')->entity;
          if ($ultimate_parent && $ultimate_parent->access('view')) {
            $parent_label = $ultimate_parent->label();
          } else {
            // Handle cases where the ultimate parent is not accessible or not found.
            $parent_label = (string) t('Unaccessible Parent Guide');
          }
        } else {
          // The parent item is directly a 'guide'.
          $parent_label = $parent_item->label();
        }

        // Initialize the parent group if it doesn't exist.
        if (!isset($groupedOptions[$parent_label])) {
          $groupedOptions[$parent_label] = [];
        }

        // Add the node to its parent group.
        $groupedOptions[$parent_label][$node->id()] = $node->label();
      }
    }

    // Sort the groups alphabetically by their label.
    ksort($groupedOptions);

    return $groupedOptions;
  }

}
