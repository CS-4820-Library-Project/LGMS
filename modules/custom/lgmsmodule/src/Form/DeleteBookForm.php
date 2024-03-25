<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class DeleteBookForm extends FormBase {

  public function getFormId() {
    return 'delete_html_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    $current_box = \Drupal::request()->query->get('current_box');
    $form['current_box'] = [
      '#type' => 'hidden',
      '#value' => $current_box,
    ];

    $current_node = \Drupal::request()->query->get('current_node');
    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $current_node,
    ];

    $current_item = \Drupal::request()->query->get('current_item');
    $form['current_item'] = [
      '#type' => 'hidden',
      '#value' => $current_item,
    ];

    $title = $this->t('<Strong>Are you Sure you want to Delete This Item?</Strong>');

    $form['Delete'] = [
      '#type' => 'checkbox',
      '#title' => $title,
      '#required' => True
    ];


    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#button_type' => 'primary',
    ];

    $form['actions']['submit']['#ajax'] = [
      'callback' => '::submitAjax',
      'event' => 'click',
    ];

    return $form;
  }

  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box was deleted Successfully.');
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $current_box = $form_state->getValue('current_box');
    $current_box = Node::load($current_box);

    $current_item = $form_state->getValue('current_item');
    $current_item = Node::load($current_item);

    $child_items = $current_box->get('field_box_items')->getValue();

    $child_items = array_filter($child_items, function ($item) use ($current_item) {
      return $item['target_id'] != $current_item->id();
    });

    $current_box->set('field_box_items', $child_items);
    $current_box->save();

    $parent_box = $current_item->get('field_parent_box')->entity;

    if($current_box->id() == $parent_box->id()){
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'guide_box')
        ->condition('field_box_items', $current_item->id())
        ->accessCheck(TRUE);
      $result = $query->execute();

      foreach ($result as $box){
        $box = Node::load($box);
        $child_items = $box->get('field_box_items')->getValue();

        $child_items = array_filter($child_items, function ($box) use ($current_item) {
          return $box['target_id'] != $current_item->id();
        });

        $box->set('field_box_items', $child_items);
        $box->save();
      }

      $current_item?->delete();
    }

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
