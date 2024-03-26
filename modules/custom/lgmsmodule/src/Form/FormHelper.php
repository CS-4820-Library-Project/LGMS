<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class FormHelper {

  /**
   * @throws EntityMalformedException
   */
  public function submitModalAjax(array &$form, FormStateInterface $form_state, String $message): AjaxResponse
  {
    // Create an array of AJAX commands.
    $response = new AjaxResponse();

    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal-form', $form));
      return $response;
    }

    // Close the modal dialog.
    $response->addCommand(new CloseModalDialogCommand());

    $curr_node = $form_state->getValue('current_node');
    $curr_node = Node::load($curr_node);

    $curr_node_url = $curr_node->toUrl()->toString();
    $curr_node_url = str_ireplace('LGMS/', '', $curr_node_url);

    $response->addCommand(new RedirectCommand(Url::fromUri('internal:' . $curr_node_url)->toString()));

    \Drupal::messenger()->addMessage($message);
    return $response;
  }

  public function updateParent(array &$form, FormStateInterface $form_state,)
  {
    $current_node = $form_state->getValue('current_node');
    $current_node = Node::load($current_node);

    if($current_node->getType() === 'guide'){
      $current_node->set('changed', \Drupal::time()->getRequestTime());
      $current_node->save();
    } else if ($current_node->getType() === 'guide_page'){
      $current_node->set('changed', \Drupal::time()->getRequestTime());
      $current_node->save();

      $guide = $current_node->get('field_parent_guide')->getValue();
      $guide = Node::load($guide[0]['target_id']);

      $guide->set('changed', \Drupal::time()->getRequestTime());
      $guide->save();
    }

    $current_node->set('changed', \Drupal::time()->getRequestTime());
    $current_node->save();
  }

  public function set_form_data(array &$form, $ids){
    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    $form['current_box'] = [
      '#type' => 'hidden',
      '#value' => property_exists($ids, 'current_box') ? $ids->current_box : null,
    ];

    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => property_exists($ids, 'current_node') ? $ids->current_node : null,
    ];

    $form['current_item'] = [
      '#type' => 'hidden',
      '#value' => property_exists($ids, 'current_item') ? $ids->current_item : null,
    ];

  }

  public function delete_item(array &$form, FormStateInterface $form_state, $field_name){
    $current_box = Node::load($form_state->getValue('current_box'));
    $current_item = Node::load($form_state->getValue('current_item'));

    // remove link to current box
    $child_items = $current_box->get('field_box_items')->getValue();
    $child_items = array_filter($child_items, function ($item) use ($current_item) {
      return $item['target_id'] != $current_item->id();
    });

    $current_box->set('field_box_items', $child_items);
    $current_box->save();

    $field = $current_item->get($field_name)->entity;

    // If this is the box the original item was created in
    if($current_item->id() == $field->get('field_parent_item')->entity->id()){
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
  }
}
