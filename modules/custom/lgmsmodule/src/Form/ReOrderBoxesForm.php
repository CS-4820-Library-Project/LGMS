<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class ReOrderBoxesForm extends FormBase {

  public function getFormId() {
    return 're_order_guide_box_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_helper = new FormHelper();

    // Get the data from the URL
    $ids = (object) [
      'current_node' => \Drupal::request()->query->get('current_node'),
    ];

    // Set the prefix, suffix, and hidden fields
    $form_helper->set_form_data($form, $ids, $this->getFormId());

    $current_node = Node::load($ids->current_node);
    $child_boxes = $current_node->get('field_child_boxes');

    // Add reorder boxes Field
    $form_helper->get_reorder_table($form, $child_boxes);


    $form['actions']['#type'] = 'actions';
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
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'The Boxes have been re-ordered.', '#'.$this->getFormId());
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    // Get the new order
    $values = $form_state->getValue('pages_table');

    // Load the current node that holds the items to be sorted
    $current_node = Node::load($form_state->getValue('current_node'));

    // Get the boxes to be sorted
    $child_boxes = $current_node->get('field_child_boxes')->getValue();

    // Get the new order
    $child_boxes = $ajaxHelper->get_new_order($values,$child_boxes);

    // Save the new order
    $current_node->set('field_child_boxes', array_values($child_boxes));
    $current_node->save();

    // Update parents
    $ajaxHelper->updateParent($form, $form_state);
  }
}
