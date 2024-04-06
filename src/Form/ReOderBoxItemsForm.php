<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class ReOderBoxItemsForm extends FormBase {

  public function getFormId() {
    return 're_order_box_items_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_helper = new FormHelper();

    // Get the data from the URL
    $ids = (object) [
      'current_node' => \Drupal::request()->query->get('current_node'),
      'current_box' => \Drupal::request()->query->get('current_box'),
    ];

    // Set the prefix, suffix, and hidden fields
    $form_helper->set_form_data($form, $ids, $this->getFormId());

    // Get the items to reorder
    $box = Node::load($ids->current_box);
    $box_items = $box->get('field_box_items');

    // Add reorder items Field
    $form_helper->get_reorder_table($form, $box_items);


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

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box Items Have been re-ordered.', '#'.$this->getFormId());
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    // Get the new order
    $values = $form_state->getValue('pages_table');

    // Load the box that holds the items to be sorted
    $current_box = Node::load($form_state->getValue('current_box'));

    // Get the items to sort
    $items = $current_box->get('field_box_items')->getValue();

    // Get the new order
    $items = $ajaxHelper->get_new_order($values,$items);

    // Save the new order
    $current_box->set('field_box_items', array_values($items));
    $current_box->save();

    // Update parents
    $ajaxHelper->updateParent($form, $form_state);
  }
}
