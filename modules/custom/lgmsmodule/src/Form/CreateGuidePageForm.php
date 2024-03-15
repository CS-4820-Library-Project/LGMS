<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class CreateGuidePageForm extends FormBase
{

  public function getFormId()
  {
    return 'create_guide_page_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $ids = null)
  {
    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    // Load the current guide node.
    $form['current_guide'] = [
      '#type' => 'hidden',
      '#value' => $ids->current_guide_id,
    ];
    $current_guide_node = Node::load($ids->current_guide_id);

    // Check if the guide node exists.
    if ($current_guide_node) {
      // Add the current guide's name and ID to the form as markup elements.
      $form['guide_info'] = [
        '#type' => 'item',
        '#markup' => $this->t('You are adding a page to the guide: @name ', [
          '@name' => $current_guide_node->label(),

        ]),
      ];
    } else {
      // If the guide does not exist, display an error message.
      \Drupal::messenger()->addError($this->t('The specified guide does not exist.'));
      // Redirect to a default route, such as the front page.
      return $form_state->setRedirect('<front>');
    }

// Title field for the guide page
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page Title'),
      '#required' => TRUE,
    ];

    $form['hide_description'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide description'),
    ];

    // Description field
    $form['field_description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#format' => 'full_html', // Set the default format or use user preferred format
      '#states' => [
        'invisible' => [
          ':input[name="hide_description"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Draft Mode'),
      '#description' => $this->t('Check this box if the page is still in draft.'),
    ];

    $form['#validate'][] = '::validateFields';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Page'),
      '#button_type' => 'primary',
    ];

    $form['actions']['submit']['#ajax'] = [
      'callback' => '::submitAjax',
      'event' => 'click',
    ];

    return $form;
  }

  public function validateFields(array &$form, FormStateInterface $form_state) {
    $hide = $form_state->getValue('hide_description');
    $desc = $form_state->getValue('field_description')['value'];
    if (!$hide && empty($desc)) {
      $form_state->setErrorByName('field_description', $this->t('Description: field is required.'));
    }
  }

  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal-form', $form));
      return $response;
    }

    $current_guide_id = $form_state->getValue('current_guide');

    // Load the current guide node.
    $current_guide_node = Node::load($current_guide_id);

    // Check if the current guide node exists and is loaded.
    if ($current_guide_node != null) {
      $title = $form_state->getValue('title');
      $body_values = $form_state->getValue('field_description');

      // Create the guide page node.
      $guide_page = Node::create([
        'type' => 'guide_page',
        'title' => $title,
        'field_description' => [
          'value' => $body_values['value'],
          'format' => $body_values['format'],
        ],
        'field_parent_guide' => [
          'target_id' => $current_guide_id,
        ],
        'status' => $form_state->getValue('published') == '0',

      ]);

      // Save the new node.
      $guide_page->save();


      $current_guide_node->set('changed', \Drupal::time()->getRequestTime());
      $current_guide_node->save();

      // Message for the user.
      \Drupal::messenger()->addMessage($this->t('The guide page has been created with ID: @id', ['@id' => $guide_page->id()]));
      $response->addCommand(new CloseModalDialogCommand());

      $response->addCommand(new RedirectCommand(Url::fromUri('internal:/node/' . $guide_page->id())->toString()));

      // Redirect to the new guide page.
    } else {
      // If the guide node does not exist, display an error message and redirect.
      \Drupal::messenger()->addError($this->t('The guide page could not be associated with a guide.'. $current_guide_id));
      $response->addCommand(new CloseModalDialogCommand());
      // Redirect to a default route such as the front page


    }

    return $response;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {

  }
}
