<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class ReOderBoxItemsForm extends FormBase {

  public function getFormId() {
    return 're_order_box_items_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
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

    $form['pages_table'] = [
      '#type' => 'table',
      '#header' => ['Title', 'Weight'],
      '#tabledrag' => [[
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'pages-order-weight',
      ]],
    ];

    $box = Node::load($current_box);

    $box_items = $box->get('field_box_items');

    foreach ($box_items as $weight => $item) {
      $loaded_item = Node::load($item->target_id);

      $form['pages_table'][$weight]['#attributes']['class'][] = 'draggable';
      $form['pages_table'][$weight]['title'] = [
        '#markup' => $loaded_item->label(),
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

    $current_box = $form_state->getValue('current_box');
    $current_box = Node::load($current_box);

    $items = $current_box->get('field_box_items')->getValue();

    $reordered_items = [];

    foreach ($values as $id => $value) {
      if (isset($items[$id])) {
        $reordered_items[$value['weight']] = $items[$id];
      }
    }

    ksort($reordered_items);

    $current_box->set('field_box_items', array_values($reordered_items));

    $current_box->save();

    $curr_node_url = $current_node->toUrl()->toString();
    $curr_node_url = str_ireplace('lgms/', '', $curr_node_url);

    $node_path = str_ireplace('lgms/', '', $curr_node_url);

    $form_state->setRedirectUrl(Url::fromUri('internal:' . $node_path));

    \Drupal::messenger()->addMessage($this->t('The Boxes Items have been re-ordered.'));
  }
}
