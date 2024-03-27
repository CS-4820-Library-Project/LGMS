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

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['#prefix'] = '<div id="' . $this->getFormId() . '">';
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

    $form['page_to_sort'] = [
      '#type' => 'select',
      '#title' => $this->t('Page To Sort:'),
      '#options' => $this->getPageList($current_guide),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateTable',
        'wrapper' => 'pages-table-wrapper',
        'event' => 'change',
      ],
    ];

    $form['current_page'] = [
      '#type' => 'hidden',
      '#value' => 'top_level',
    ];

    $form['pages_table_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'pages-table-wrapper'],
    ];

    $form['pages_table_wrapper']['pages_table'] = [
      '#type' => 'table',
      '#header' => ['Title', 'Weight'],
      '#tabledrag' => [[
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'pages-order-weight',
      ]],
    ];

    $guide = $form_state->getValue('page_to_sort') != null ? Node::load($form_state->getValue('page_to_sort')): null;

    if($guide && !$guide->get('field_child_pages')->isEmpty()){
      $page_list = $guide->get('field_child_pages');
      foreach ($page_list as $weight => $page) {
        $loaded_item = Node::load($page->target_id);

        $form['pages_table_wrapper']['pages_table'][$weight]['#attributes']['class'][] = 'draggable';
        $form['pages_table_wrapper']['pages_table'][$weight]['title'] = [
          '#markup' => $loaded_item->label(),
        ];

        $form['pages_table_wrapper']['pages_table'][$weight]['weight'] = [
          '#type' => 'weight',
          '#title' => t('Weight'),
          '#title_display' => 'invisible',
          '#default_value' => $weight,
          '#attributes' => ['class' => ['pages-order-weight']],
        ];
      }
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

  public function updateTable(array &$form, FormStateInterface $form_state) {

    return $form['pages_table_wrapper'];
  }


  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box Items Have been re-ordered.', '#'.$this->getFormId());
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('pages_table');

    $selected_page = $form_state->getValue('page_to_sort');
    $guide = Node::load($form_state->getValue('guide_id'));

    $parent = Node::load($selected_page);


    $items = $parent->get('field_child_pages')->getValue();

    \Drupal::logger('my_modul22e')->notice('<pre>' . print_r($items, TRUE) . '</pre>');
    \Drupal::logger('my_modul22e')->notice('<pre>' . print_r($values, TRUE) . '</pre>');

    $reordered_items = [];

    foreach ($values as $id => $value) {
      if (isset($items[$id])) {
        $reordered_items[$value['weight']] = $items[$id];
      }
    }

    ksort($reordered_items);

    $parent->set('field_child_pages', array_values($reordered_items));
    $parent->save();

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }

  public function getPageList($guide_id) {
    $options = [];

    // Load the guide entity.
    $guide = Node::load($guide_id);

    $options['Page Level'][$guide->id()] = t('Page Level');

    // Check if the guide has been loaded and has the field_child_pages field.
    if ($guide && $guide->hasField('field_child_pages')) {
      // Get the array of child page IDs from the guide.
      $child_pages = $guide->get('field_child_pages')->referencedEntities();

      if (!empty($child_pages)) {
        // Create options array from the child pages.
        foreach ($child_pages as $child_page) {
          // Check if the child page itself has child pages
          if ($child_page->hasField('field_child_pages')) {
            $sub_child_pages = $child_page->get('field_child_pages')->referencedEntities();
            if (!empty($sub_child_pages)) {
              $options[$child_page->id()] = $child_page->label(); // Include the page if it has child pages
            }
          }
        }
      }
    }

    // Return the options array with the 'Top Level' and the grouped child pages.
    return $options;
  }
}

//'#access' => $id == 'Top Level',
