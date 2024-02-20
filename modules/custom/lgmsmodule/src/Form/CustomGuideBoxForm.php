<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class CustomGuideBoxForm extends FormBase {

  public function getFormId() {
    return 'custom_guide_box_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_node = \Drupal::request()->query->get('current_node');
    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $current_node,
    ];

    $form['#attributes']['id'] = 'form-selector';

    // Title field
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
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
    $curr_node = $form_state->getValue('current_node');
    $curr_node = Node::load($curr_node);
    $nid = $curr_node->id();


    if ($curr_node->bundle() === 'guide'){
      // Get the list of guide pages
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'guide_page')
        ->condition('field_parent_guide', $curr_node->id())
        ->accessCheck(TRUE);
      $result = $query->execute();

      // Get the first page
      $first_node_id = reset($result);
      $page = Node::load($first_node_id);

      $nid = $page->id();
    }

    $new_node = Node::create([
      'type' => 'guide_box',
      'title' => $form_state->getValue('title'),
      'field_body_box' => [
        'value' => $form_state->getValue('body'),
        'format' => 'full_html',
      ],
      'field_parent_page' => ['target_id' => $nid],
    ]);

    $new_node->save();


    \Drupal::messenger()->addMessage('Box created successfully.');

    $curr_node_url = $curr_node->toUrl()->toString();
    $curr_node_url = str_replace('LGMS/', '', $curr_node_url);

    $node_path = str_replace('LGMS/', '', $curr_node_url);

    $form_state->setRedirectUrl(Url::fromUri('internal:' . $node_path));
  }
}
