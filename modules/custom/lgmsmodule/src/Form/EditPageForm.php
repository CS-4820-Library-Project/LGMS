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

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Assuming you pass the Node object as a parameter to the form.
    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    $current_node = \Drupal::request()->query->get('current_node');
    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $current_node,
    ];

    $current_page_id = \Drupal::request()->query->get('current_page');

    // Load the node if it exists
    $current_page = $current_page_id ? Node::load($current_page_id) : NULL;



    if (!$current_page) {
      // Display an error message if no guide page is provided.
      \Drupal::messenger()->addError($this->t('The guide page could not be loaded.'));
      return $form_state->setRedirect('<front>');
    }

    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    // Store the current guide page ID in a hidden field.
    $form['guide_page_id'] = [
      '#type' => 'hidden',
      '#value' => $current_page->id(),
    ];

    // Title field pre-filled with the existing title.
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page Title'),
      '#required' => TRUE,
      '#default_value' => $current_page->getTitle(),
    ];

    // Description field pre-filled with the existing body.
    $form['hide_description'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide description'),
      '#default_value' => $current_page->get('field_hide_description')->value,
    ];

// Description field with state controlled by hide_description checkbox.
    $form['description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#default_value' => $current_page->get('field_description')->value,
      '#format' =>$current_page->get('field_description')->format,
      '#states' => [
        'invisible' => [
          ':input[name="hide_description"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['draft_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Draft Mode'),
      '#default_value' => $current_page->isPublished() ? 0 : 1, // Assuming '0' is draft and published is '1'.
      '#description' => $this->t('Check this box if the page is still in draft mode.'),
    ];

    // Submit button.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Changes'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Load the guide page node from the form state.
    $guide_page_id = $form_state->getValue('guide_page_id');
    $guide_page = Node::load($guide_page_id);

    if ($guide_page) {
      // Set the new title and description from the form state values.
      $guide_page->setTitle($form_state->getValue('title'));
      $guide_page->set('field_hide_description', $form_state->getValue('hide_description'));
      $guide_page->set('field_description', [
        'value' => $form_state->getValue('description')['value'],
        'format' => $form_state->getValue('description')['format'],
      ]);
      $status = $form_state->getValue('draft_mode') ? 0 : 1; // '0' for unpublished, '1' for published.
      $guide_page->set('status', $status);

      // Save the updated node.
      $guide_page->save();

      \Drupal::messenger()->addMessage($this->t('The guide page has been updated.'));

      // Redirect to the updated guide page.
      $form_state->setRedirect('entity.node.canonical', ['node' => $guide_page_id]);
    } else {
      \Drupal::messenger()->addError($this->t('The guide page could not be loaded.'));
    }
  }
}
