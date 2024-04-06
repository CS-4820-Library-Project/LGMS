<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class EditPageForm extends FormBase {

  public function getFormId() {
    return 'edit_page_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state){
    // Set the prefix, suffix, and hidden fields
    $form_helper = new FormHelper();

    // Get the data from the URL
    $ids = (object) [
      'guide_id' => \Drupal::request()->query->get('guide_id'),
    ];

    // Set the prefix, suffix, and hidden fields
    $form_helper->set_form_data($form, $ids, $this->getFormId());

    // Select Page Field
    $form['select_page'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Page'),
      '#options' => $form_helper->get_pages_options($ids->guide_id,false),
      '#empty_option' => $this->t('- Select a Page -'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::selectPageCallBack',
        'wrapper' => 'update-wrapper',
        'event' => 'change',
      ],
    ];

    // Wrapper to update when page selection changes
    $form['update_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'update-wrapper'],
    ];

    // Get the selected page
    $selected = $form_state->getValue('select_page', '');

    // If there is a page selected
    if(!empty($selected)) {
      // Load the page
      $selected_node = Node::load($selected);
      $reference = !$selected_node->get('field_reference_node')->isEmpty();

      // Node to redirect the user to
      $form['update_wrapper']['current_node'] = [
        '#type' => 'hidden',
        '#value' => $selected_node->id(),
      ];

      // Title field pre-filled with the existing title.
      $form['update_wrapper']['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Page Title'),
        '#default_value' => $reference? t('This is Just a Link and can not be Edited') : $selected_node->label(),
        '#required' => !$reference,
        '#disabled' => $reference,
      ];

      // Description field pre-filled with the existing body.
      $form['update_wrapper']['hide_description'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Hide description'),
        '#default_value' => $selected_node->get('field_hide_description')->value,
        '#disabled' => $reference,
        '#ajax' => [
          'callback' => '::hideDescriptionCallback', // Update the form to trigger resizing
          'wrapper' => 'update-wrapper',
          'event' => 'change',
        ],
      ];

      // Description field with state controlled by hide_description checkbox.
      $form['update_wrapper']['description'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Description'),
        '#states' => [
          'invisible' => [
            ':input[name="hide_description"]' => ['checked' => TRUE],
          ],
          'required' => [
            ':input[name="hide_description"]' => ['checked' => False],
          ],
        ],
        '#default_value' => $selected_node->get('field_description')->value,
        '#format' => $selected_node->get('field_description')->format,
        '#disabled' => $reference,
      ];

      // The position field prefilled with the current page position
      $form['update_wrapper']['position'] = [
        '#type' => 'select',
        '#title' => $this->t('Position'),
        '#options' => $form_helper->get_position_options($form_state, $ids->guide_id),
        '#default_value' => !$selected_node->get('field_child_pages')->isEmpty()? $ids->guide_id : $selected_node->get('field_parent_guide')->entity->id(),
        '#disabled' => !$selected_node->get('field_child_pages')->isEmpty(),
        '#required' => TRUE,
      ];

      // if it's a reference check if the page it references is published
      $disable = !$selected_node->get('field_reference_node')->isEmpty() && !$selected_node->get('field_reference_node')->entity->isPublished();

      $form['update_wrapper']['draft_mode'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Draft Mode'),
        '#description' => $disable? $this->t('The Linked page is unPublished. Publish it to be able to update this Page.') : $this->t('Check this box if the page is still in draft mode.'),
        '#default_value' => !$selected_node->isPublished(),
        '#disabled' => $disable,
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Edit'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::submitAjax',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  public function hideDescriptionCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Trigger resizing
    $response->addCommand(new ReplaceCommand('#update-wrapper', $form['update_wrapper']));

    return $response;
  }


  public function submitAjax(array &$form, FormStateInterface $form_state) {
    // Create an array of AJAX commands.
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Page updated successfully.', '#'.$this->getFormId());
  }


  public function selectPageCallBack(array &$form, FormStateInterface $form_state) {
    // Get the selected page
    $selected = $form_state->getValue('select_page', '');
    $selected_node = $selected? Node::load($selected) : null;

    if ($selected_node){
      // Update the field's values to match the selected page's values
      $form['update_wrapper']['title']['#value'] = $selected_node->label();
      $form['update_wrapper']['description']['value']['#value'] = $selected_node->get('field_description')->value;
      $form['update_wrapper']['hide_description']['#checked'] = $selected_node->get('field_hide_description')->value;
      $form['update_wrapper']['draft_mode']['#checked'] = !$selected_node->isPublished();
    }

    return $form['update_wrapper'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    // Load the selected page
    $selected_page = Node::load($form_state->getValue('select_page'));

    if (!$selected_page->get('field_reference_node')->isEmpty()){
      // If the page is a reference, the user can only change the draft mode
      $form_state->getValue('draft_mode') == '0'? $selected_page->setPublished(): $selected_page->setUnpublished();
      $selected_page->save();
    } else {
      // Otherwise, Update all values
      $selected_page->setTitle($form_state->getValue('title'));
      $selected_page->set('field_hide_description', $form_state->getValue('hide_description') == '1');
      $selected_page->set('field_description', [
        'value' => $form_state->getValue('description')['value'],
        'format' => $form_state->getValue('description')['format'],
      ]);
      $form_state->getValue('draft_mode') == '0'? $selected_page->setPublished(): $selected_page->setUnpublished();

      // If the position has changed
      if($selected_page->hasField('field_parent_guide') && $form_state->getValue('position') != $selected_page->get('field_parent_guide')->entity->id()){
        $parent = $selected_page->get('field_parent_guide')->entity;

        // Remove the selected page from the old position
        $ajaxHelper->remove_child($parent, $selected_page, 'field_child_pages');

        // Load the new position
        $new_parent = Node::load($form_state->getValue('position'));

        // Set the new position as the parent
        $selected_page->set('field_parent_guide', $new_parent);
        $selected_page->save();

        // Add the page to the new position's children
        $ajaxHelper->add_child_page($new_parent, $selected_page);
      }
    }

    // Update Parents
    $ajaxHelper->updateParent($form, $form_state);
  }

}
