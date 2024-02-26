<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class DeleteGuideBoxForm extends FormBase {

  public function getFormId() {
    return 'delete_guide_box_form';
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


    $form['Delete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<Strong>Are you Sure you want to Delete This Box?</Strong>
                                if you delete this box, it will be permanently Deleted and restoring it would be impossible!!'),
      '#required' => True
    ];


    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $current_box = $form_state->getValue('current_box');
    $current_box = Node::load($current_box);

    $current_node = $form_state->getValue('current_node');
    $current_node = Node::load($current_node);

    $page = $current_node;

    if ($current_node->bundle() === 'guide'){
      // Get the list of guide pages
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'guide_page')
        ->condition('field_parent_guide', $current_node->id())
        ->accessCheck(TRUE);
      $result = $query->execute();

      // Get the first page
      $first_node_id = reset($result);
      $page = Node::load($first_node_id);
    }

    $child_boxes = $page->get('field_child_boxes')->getValue();

    $child_boxes = array_filter($child_boxes, function ($box) use ($current_box) {
      return $box['target_id'] != $current_box->id();
    });

    $page->set('field_child_boxes', $child_boxes);
    $page->save();

    $current_box?->delete();


    $curr_node_url = $current_node->toUrl()->toString();
    $curr_node_url = str_ireplace('lgms/', '', $curr_node_url);

    $node_path = str_ireplace('lgms/', '', $curr_node_url);

    $form_state->setRedirectUrl(Url::fromUri('internal:' . $node_path));

    \Drupal::messenger()->addMessage('Box Was Deleted successfully.');
  }
}
