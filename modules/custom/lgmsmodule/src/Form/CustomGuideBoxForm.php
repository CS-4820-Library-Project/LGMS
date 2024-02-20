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

class CustomGuideBoxForm extends FormBase {

  public function getFormId() {
    return 'custom_guide_box_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_node_url = \Drupal::request()->query->get('current_node_url');
    $form['current_node_url'] = [
      '#type' => 'hidden',
      '#value' => $current_node_url,
    ];

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

    \Drupal::messenger()->addMessage('Box created successfully.');

    $current_url = $form_state->getValue('current_node_url');
    $node_path = str_replace('LGMS/', '', $current_url);

    $form_state->setRedirectUrl(Url::fromUri('internal:' . $node_path));
  }
}
