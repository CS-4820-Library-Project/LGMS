<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityInterface;
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
    $this->set_prefix($form);

    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => property_exists($ids, 'current_node') ? $ids->current_node : null,
    ];

    $form['current_box'] = [
      '#type' => 'hidden',
      '#value' => property_exists($ids, 'current_box') ? $ids->current_box : null,
    ];

    $form['current_item'] = [
      '#type' => 'hidden',
      '#value' => property_exists($ids, 'current_item') ? $ids->current_item : null,
    ];
  }

  public function set_prefix(array &$form){
    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];
  }

  public function create_link(EntityInterface $new_content, String $current_box)
  {
    //find what item is being created
    $field_name = '';
    if ($new_content->bundle() == 'guide_html_item'){
      $field_name = 'field_html_item';
    } elseif ($new_content->bundle() == 'guide_book_item'){
      $field_name = 'field_book_item';
    } elseif ($new_content->bundle() == 'guide_database_item'){
      $field_name = 'field_database_item';
    }

    // Create the item
    $new_item = Node::create([
      'type' => 'guide_item',
      'title' => $new_content->label(),
      $field_name => $new_content,
      'field_parent_box' => $current_box,
      'status' => $new_content->isPublished(),
    ]);

    $new_item->save();

    // Update the box item list
    $current_box = Node::load($current_box);
    $boxList = $current_box->get('field_box_items')->getValue();
    $boxList[] = ['target_id' => $new_item->id()];

    $current_box->set('field_box_items', $boxList);
    $current_box->save();

    return $new_item;
  }

  public function get_filled_field($current_item): string
  {
    $possible_fields = $this->get_fields();
    $field_to_delete = '';

    foreach ($possible_fields as $field_name) {
      if (!$current_item->get($field_name)->isEmpty()) {
        $field_to_delete = $field_name;
        break;
      }
    }

    return $field_to_delete;
  }

  public function get_fields(): array
  {
    return [
      'field_database_item',
      'field_html_item',
      'field_book_item',
      'field_media_image'
    ];
  }

  public function deletePages($parent, $delete_sub){
    $this->deleteBoxes($parent);

    if($delete_sub) {
      $pages = $parent->get('field_child_pages')->referencedEntities();
      foreach ($pages as $page) {
        $this->deleteBoxes($page);
        $this->deletePages($page, $delete_sub);
      }
    }

    $parent->delete();
  }

  public function deleteBoxes($parent): void
  {
    $boxes = $parent->get('field_child_boxes')->referencedEntities();

    foreach($boxes as $box){
      if($parent->id() == $box->get('field_parent_node')->entity->id()){

        $query = \Drupal::entityQuery('node')
          ->condition('type', 'guide_page')
          ->condition('field_child_boxes', $box->id())
          ->accessCheck(TRUE);
        $result = $query->execute();

        foreach ($result as $page){
          $page = Node::load($page);
          $child_boxes = $page->get('field_child_boxes')->getValue();

          $child_boxes = array_filter($child_boxes, function ($box_new) use ($box) {
            return $box_new['target_id'] != $box->id();
          });

          $page->set('field_child_boxes', $child_boxes);
          $page->save();
        }

        $box?->delete();
      }
    }
  }
}
