<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class DeleteDatabaseForm extends FormBase {

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

    $current_box_content = \Drupal::request()->query->get('current_box_content');
    $form['current_box_content'] = [
      '#type' => 'hidden',
      '#value' => $current_box_content,
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

    $current_box_content = $form_state->getValue('current_box_content');
    $current_box_content = Node::load($current_box_content);

    $current_item = $form_state->getValue('current_item');
    $current_item = Node::load($current_item);

    $child_items = $current_box_content->get('field_box_items')->getValue();

    $child_items = array_filter($child_items, function ($item) use ($current_item) {
      return $item['target_id'] != $current_item->id();
    });

    $current_box_content->set('field_box_items', $child_items);
    $current_box_content->save();

    $parent_content = $current_item->get('field_parent_box_content')->entity;

    if($current_box_content->id() == $parent_content->id()){
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'guide_box_content')
        ->condition('field_box_items', $current_item->id())
        ->accessCheck(TRUE);
      $result = $query->execute();

      foreach ($result as $content){
        $content = Node::load($content);
        $child_items = $content->get('field_box_items')->getValue();

        $child_items = array_filter($child_items, function ($box) use ($current_item) {
          return $box['target_id'] != $current_item->id();
        });

        $content->set('field_box_items', $child_items);
        $content->save();
      }

      $current_item?->delete();
    }

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
