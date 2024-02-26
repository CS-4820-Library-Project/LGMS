<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class ReOrderBoxesForm extends FormBase {

  public function getFormId() {
    return 're_order_guide_box_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_node = \Drupal::request()->query->get('current_node');
    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $current_node,
    ];

    $form['pages_table'] = [
      '#type' => 'table',
      '#header' => ['Title', 'Weight'],
      '#tabledrag' => [[
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'pages-order-weight',
      ]],
    ];

    $page = Node::load($current_node);

    if ($page && $page->bundle() === 'guide'){
      // Get the list of guide pages
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'guide_page')
        ->condition('field_parent_guide', $page->id())
        ->accessCheck(TRUE);
      $result = $query->execute();

      // Get the first page
      $first_node_id = reset($result);
      $page = Node::load($first_node_id);
    }

    $child_boxes = $page->get('field_child_boxes');

    foreach ($child_boxes as $weight => $box) {
      $child_page = Node::load($box->target_id);

      $form['pages_table'][$weight]['#attributes']['class'][] = 'draggable';
      $form['pages_table'][$weight]['title'] = [
        '#markup' => $child_page->label(),
      ];

      $form['pages_table'][$weight]['weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => ['class' => ['pages-order-weight']],
      ];
    }


    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('pages_table');

    $current_node = $form_state->getValue('current_node');
    $current_node = Node::load($current_node);

    $page = $current_node;

    if ($page && $page->bundle() === 'guide'){
      // Get the list of guide pages
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'guide_page')
        ->condition('field_parent_guide', $page->id())
        ->accessCheck(TRUE);
      $result = $query->execute();

      // Get the first page
      $first_node_id = reset($result);
      $page = Node::load($first_node_id);
    }

    $child_boxes = $page->get('field_child_boxes')->getValue();

    $reordered_child_boxes = [];

    foreach ($values as $id => $value) {
      if (isset($child_boxes[$id])) {
        $reordered_child_boxes[$value['weight']] = $child_boxes[$id];
      }
    }

    ksort($reordered_child_boxes);

    $page->set('field_child_boxes', array_values($reordered_child_boxes));

    $page->save();

    $curr_node_url = $current_node->toUrl()->toString();
    $curr_node_url = str_ireplace('lgms/', '', $curr_node_url);

    $node_path = str_ireplace('lgms/', '', $curr_node_url);

    $form_state->setRedirectUrl(Url::fromUri('internal:' . $node_path));

    \Drupal::messenger()->addMessage($this->t('The Boxes have been re-ordered.'));
  }
}
