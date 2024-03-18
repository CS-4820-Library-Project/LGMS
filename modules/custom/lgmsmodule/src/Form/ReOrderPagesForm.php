<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class ReOrderPagesForm extends FormBase {

  public function getFormId() {
    return 're_order_box_items_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    $current_guide = \Drupal::request()->query->get('guide_id');
    $form['guide_id'] = [
      '#type' => 'hidden',
      '#value' => $current_guide,
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

    $guide = Node::load($current_guide);

    $page_list = $guide->get('field_child_pages');

    foreach ($page_list as $weight => $page) {
      $loaded_item = Node::load($page->target_id);

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

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box Items Have been re-ordered.');
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('pages_table');

    $current_box = $form_state->getValue('guide_id');
    $current_box = Node::load($current_box);

    $items = $current_box->get('field_child_pages')->getValue();

    $reordered_items = [];

    foreach ($values as $id => $value) {
      if (isset($items[$id])) {
        $reordered_items[$value['weight']] = $items[$id];
      }
    }

    ksort($reordered_items);

    $current_box->set('field_child_pages', array_values($reordered_items));
    $current_box->save();

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
