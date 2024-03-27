<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DeleteHTMLForm extends FormBase {

  public function getFormId() {
    return 'delete_html_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_helper = new FormHelper();
    $form['#prefix'] = '<div id="' . $this->getFormId() . '">';

    $current_node = \Drupal::request()->query->get('current_node');
    $current_box = \Drupal::request()->query->get('current_box');
    $current_item = \Drupal::request()->query->get('current_item');

    if (empty($current_box) || empty($current_node) || empty($current_item)){
      throw new AccessDeniedHttpException();
    }

    $form['current_box'] = [
      '#type' => 'hidden',
      '#value' => $current_box,
    ];

    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $current_node,
    ];

    $form['current_item'] = [
      '#type' => 'hidden',
      '#value' => $current_item,
    ];

    $current_item = Node::load($current_item);
    $field_to_delete = '';
    $possible_fields = $form_helper->get_fields();

    foreach ($possible_fields as $field_name) {
      if (!$current_item->get($field_name)->isEmpty()) {
        $field_to_delete = $field_name;
        break;
      }
    }

    $form['field_name'] = [
      '#type' => 'hidden',
      '#value' => $field_to_delete,
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

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Item was deleted Successfully.', '#'.$this->getFormId());
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_box = Node::load($form_state->getValue('current_box'));
    $current_item = Node::load($form_state->getValue('current_item'));
    $field_name = $form_state->getValue('field_name');

    // remove link to current box
    $child_items = $current_box->get('field_box_items')->getValue();
    $child_items = array_filter($child_items, function ($item) use ($current_item) {
      return $item['target_id'] != $current_item->id();
    });

    $current_box->set('field_box_items', $child_items);
    $current_box->save();

    $field = $current_item->get($field_name)->entity;

    // If this is the box the original item was created in
    if($field->hasField('field_parent_item') && $current_item->id() == $field->get('field_parent_item')->entity->id()){
      // Get all guide_item that point to this field
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'guide_item')
        ->condition($field_name, $field->id())
        ->accessCheck(false);
      $result = $query->execute();

      // Go though all items that reference the given node and delete them
      foreach ($result as $item){
        $item = Node::load($item);
        $parent_box = $item->get('field_parent_box')->entity;

        // remove link to current box
        $child_items = $parent_box->get('field_box_items')->getValue();
        $child_items = array_filter($child_items, function ($box) use ($item) {
          return $box['target_id'] != $item->id();
        });

        $parent_box->set('field_box_items', $child_items);
        $parent_box->save();

        $item?->delete();
      }

      $field?->delete();
    }

    $current_item?->delete();

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
