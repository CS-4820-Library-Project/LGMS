<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class CustomGuideBoxForm extends FormBase {

  public function getFormId() {
    return 'custom_guide_box_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['id'] = 'form-selector';

    // Title field
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#required' => TRUE,
    ];

    // Parent page entity reference field
    $form['parent_page'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Parent Page'),
      '#target_type' => 'node', // Adjust according to your needs
      '#selection_settings' => [
        'target_bundles' => ['guide_page'], // Adjust to your guide page bundle
      ],
      '#required' => TRUE,
    ];

    // Body field
    $form['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::ajaxSubmitCallback',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle form submission logic, like saving data
    $node = Node::create([
      'type' => 'guide_box',
      'title' => $form_state->getValue('title'),
      'field_body_box' => [
        'value' => $form_state->getValue('body'),
        'format' => 'full_html',
      ],
      'field_parent_page' => ['target_id' => $form_state->getValue('parent_page')],
    ]);

    $node->save();

    \Drupal::messenger()->addMessage('Content created successfully.');
    $form_state->setRebuild();
  }

  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {
    // Close the modal after submission
    $response = new AjaxResponse();

    // Check if there are any form errors.
    if ($form_state->hasAnyErrors()) {
      // Return the form if there are errors.
      $response->addCommand(new ReplaceCommand('#form-selector', $form));
    } else {
      // If the form submission is successful, refresh the page and close the window.
      $node = Node::create([
        'type' => 'guide_box',
        'title' => $form_state->getValue('title'),
        'field_body_box' => [
          'value' => $form_state->getValue('body'),
          'format' => 'full_html',
        ],
        'field_parent_page' => ['target_id' => $form_state->getValue('parent_page')],
      ]);

      $node->save();

      $response->addCommand(new CloseModalDialogCommand());
      $current_url = \Drupal\Core\Url::fromRoute('<current>');
      $response->addCommand(new RedirectCommand($current_url->toString()));
      \Drupal::messenger()->addMessage('Content created successfully.');
    }

    return $response;
  }
}
