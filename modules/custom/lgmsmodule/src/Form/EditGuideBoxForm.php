<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class EditGuideBoxForm extends FormBase {

  public function getFormId() {
    return 'edit_guide_box_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_node = \Drupal::request()->query->get('current_node');
    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $current_node,
    ];

    $current_box = \Drupal::request()->query->get('current_box');
    $form['current_box'] = [
      '#type' => 'hidden',
      '#value' => $current_box,
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('New Box Title:'),
      '#required' => TRUE,
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

    $current_box = $form_state->getValue('current_box');
    $current_box = Node::load($current_box);

    $current_box->setTitle(rtrim($form_state->getValue('title')));
    $current_box->save();

    $current_node = $form_state->getValue('current_node');
    $current_node = Node::load($current_node);

    $curr_node_url = $current_node->toUrl()->toString();
    $curr_node_url = str_replace('lgms/', '', $curr_node_url);

    $node_path = str_replace('lgms/', '', $curr_node_url);

    $form_state->setRedirectUrl(Url::fromUri('internal:' . $node_path));

    \Drupal::messenger()->addMessage('Box Was updated successfully.');
  }
}
