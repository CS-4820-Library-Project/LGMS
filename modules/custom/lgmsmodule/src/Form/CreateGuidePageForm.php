<?php
namespace Drupal\lgmsmodule\Form;

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

  public function buildForm(array $form, FormStateInterface $form_state)
  {


    $current_guide_id = \Drupal::request()->query->get('current_guide');

    // Load the current guide node.
    $current_guide_node = Node::load($current_guide_id);

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

    // Description field
    $form['field_description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#format' => 'full_html', // Set the default format or use user preferred format
      '#required' => TRUE,
    ];
    $form['field_draft_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Draft Mode'),
      '#description' => $this->t('Check this box if the page is still in draft.'),
    ];

// Submit button
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Page'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $current_guide_id = \Drupal::request()->query->get('current_guide');

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
        'field_draft_mode' => $form_state->getValue('field_draft_mode'), // Capture the draft mode value.

      ]);

      // Save the new node.
      $guide_page->save();


      $current_guide_node->set('changed', \Drupal::time()->getRequestTime());
      $current_guide_node->save();

      // Message for the user.
      \Drupal::messenger()->addMessage($this->t('The guide page has been created with ID: @id', ['@id' => $guide_page->id()]));

      // Redirect to the new guide page.
      $form_state->setRedirect('entity.node.canonical', ['node' => $guide_page->id()]);
    } else {
      // If the guide node does not exist, display an error message and redirect.
      \Drupal::messenger()->addError($this->t('The guide page could not be associated with a guide.'));
      // Redirect to a default route such as the front page


    }
  }
}
