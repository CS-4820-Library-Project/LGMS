<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use DrupalCodeGenerator\Command\Yml\Links\Contextual;

/**
 * Provides a form to reuse existing guide box items.
 */
class ReuseGuideBoxForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'reuse_guide_box_form';
  }

  /**
   * Builds the form for reusing a guide box.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @param $ids Contextual IDs or parameters passed to the form.
   * @return array The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ids = null): array
  {
    // Set the prefix, suffix, and hidden fields
    $form_helper = new FormHelper();
    $form_helper->set_form_data($form,$ids, $this->getFormId());

    $form['box_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a Box'),
      '#options' => $form_helper->get_item_options('guide_box', 'field_parent_node'),
      '#empty_option' => $this->t('- Select a Box -'),
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
      '#title' => $this->t('<Strong>Link:</Strong> By selecting this, a link of the box will be created. it will be un-editable from this guide/page'),
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
      '#ajax' => [
        'callback' => '::submitAjax',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * Validates the form fields.
   *
   * @param array &$form
   * @param FormStateInterface $form_state
   */
  public function validateFields(array &$form, FormStateInterface $form_state): void
  {
    $reference = $form_state->getValue('reference');
    $title = $form_state->getValue('title');

    $curr_node = Node::load($form_state->getValue('current_node'));
    $box = Node::load($form_state->getValue('box_select'));

    $box_parent = $box->get('field_parent_node')->target_id;

    // Check if title is filled
    if (!$reference && empty($title)) {
      $form_state->setErrorByName('title', $this->t('Box Title: field is required.'));
    }

    // User can not make reference to a box inside the same page
    if ($reference && $curr_node->id() == $box_parent){
      if ($curr_node->bundle() == 'guide'){
        $form_state->setErrorByName('reference', $this->t('This box cannot be created with the same guide
          as its reference. Please select a different guide or remove the reference to proceed.'));
      }else {
        $form_state->setErrorByName('reference', $this->t('This box cannot be created with the same page
          as its reference. Please select a different page or remove the reference to proceed.'));
      }
    }
  }

  /**
   * Pre-fills form fields based on the selected guide box.
   *
   * @param array &$form
   * @param FormStateInterface $form_state
   */
  private function prefillSelectedBoxItem(array &$form, FormStateInterface $form_state): void
  {
    $selected = $form_state->getValue('box_select');

    if (!empty($selected)) {
      $selected_node = Node::load($selected);
      if ($selected_node) {
        // Update the title field based on the selected box
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

  /**
   * AJAX callback for when a box is selected.
   *
   * @param array &$form
   * @param FormStateInterface $form_state
   * @return array The updated part of the form.
   */
  public function boxItemSelectedAjaxCallback(array &$form, FormStateInterface $form_state): array
  {
    $selected = $form_state->getValue('box_select');
    $selected_node = Node::load($selected);

    // Update the title field based on the selected box
    if ($selected_node){
      $form['update_wrapper']['title']['#value'] = $selected_node->label();
    }

    return $form['update_wrapper'];
  }

  /**
   * AJAX callback for submitting the form.
   *
   * @param array &$form
   * @param FormStateInterface $form_state
   * @return AjaxResponse
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box created successfully.', '#'.$this->getFormId());
  }

  /**
   * Handles the form submission.
   *
   * Processes the reuse of the selected guide box based on form inputs.
   *
   * @param array &$form
   * @param FormStateInterface $form_state
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $ajaxHelper = new FormHelper();

    // Load the current node
    $curr_node = $form_state->getValue('current_node');
    $curr_node = Node::load($curr_node);

    // Load the selected box and it's parent
    $box = Node::load($form_state->getValue('box_select'));
    $box_parent = $box->get('field_parent_node')->target_id;

    // If the user is creating a copy
    if(!$form_state->getValue('reference')){
      // Create a copy of the box
      $new_box = $box->createDuplicate();

      // Upadte it's values
      $new_box->set('field_parent_node', $curr_node->id());
      $new_box->set('title', $form_state->getValue('title'));
      $new_box->setOwnerId(\Drupal::currentUser()->id());
      $new_box->save();

      // Get the list of items the box has
      $items = $box->get('field_box_items')->referencedEntities();

      $new_items_list = [];
      // Loop through all the items and create copy of the,
      foreach ($items as $item){
        // Create a copy of the item and update it's owner
        $new_item = $item->createDuplicate();
        $new_item->set('field_parent_box', $new_box);
        $new_item->setOwnerId(\Drupal::currentUser()->id());

        $filled_field = '';

        // Look for the filled field and copy create a copy of it and attach it to the new item
        if ($item->hasField('field_html_item') && !$item->get('field_html_item')->isEmpty()) {
          $filled_field = 'field_html_item';

        } elseif ($item->hasField('field_database_item') && !$item->get('field_database_item')->isEmpty()) {
          $filled_field = 'field_database_item';

        } elseif ($item->hasField('field_book_item') && !$item->get('field_book_item')->isEmpty()) {
          $filled_field = 'field_book_item';

        } elseif ($item->hasField('field_media_image') && !$item->get('field_media_image')->isEmpty()) {
          $filled_field = 'field_media_image';
          $media = $item->get('field_media_image')->entity;
          $new_item->set('field_media_image', $media);
        }

        // Add the field
        if ($filled_field != 'field_media_image'){
          $field = $item->get($filled_field)->entity;
          $field = $field->createDuplicate();
          $field->setOwnerId(\Drupal::currentUser()->id());
          $field->save();

          $new_item->set($filled_field, $field);
        }

        // Add the item to the list
        $new_item->save();
        $new_items_list[] = $new_item;
      }

      // Save the list of items
      $new_box->set('field_box_items', $new_items_list);
      $new_box->save();

      $box = $new_box;
    }


    // Updating the page's child boxes list with the new box
    $ajaxHelper->add_child($curr_node, $box, 'field_child_boxes');

    // Update the parents
    $ajaxHelper->updateParent($form, $form_state);
  }
}
