<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class ReOrderPagesForm extends FormBase {

  public function getFormId(): string
  {
    return 're_order_box_items_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form_helper = new FormHelper();

    // Get the data from the URL
    $ids = (object) [
      'current_node' => \Drupal::request()->query->get('current_node'),
      'guide_id' => \Drupal::request()->query->get('guide_id'),
    ];

    // Set the prefix, suffix, and hidden fields
    $form_helper->set_form_data($form, $ids, $this->getFormId());

    $form['current_page'] = [
      '#type' => 'hidden',
      '#value' => $ids->current_guide,
    ];

    // A select field to choose the page to be sorted
    $form['page_to_sort'] = [
      '#type' => 'select',
      '#title' => $this->t('Pages To Sort:'),
      '#options' => $form_helper->get_position_options($form_state, $ids->guide_id, true),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateTable',
        'wrapper' => 'pages-table-wrapper',
        'event' => 'change',
      ],
    ];

    // A wrapper to update the table based on the selection
    $form['pages_table_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'pages-table-wrapper'],
    ];

    $selected_page = $form_state->getValue('page_to_sort');
    $selected_page = $selected_page? Node::load($selected_page): null;

    if($selected_page && !$selected_page->get('field_child_pages')->isEmpty()){
      // Table header
      $form['pages_table_wrapper']['pages_table'] = [
        '#type' => 'table',
        '#header' => ['Title', 'Weight'],
        '#tabledrag' => [[
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'pages-order-weight',
        ]],
      ];

      // Get the child pages
      $page_list = $selected_page->get('field_child_pages');

      foreach ($page_list as $weight => $page) {
        // Load the child page
        $loaded_item = Node::load($page->target_id);

        if($loaded_item){
          // initialize row with the page's current weight
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
    }


    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::submitAjax',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  public function updateTable(array &$form, FormStateInterface $form_state) {
    return $form['pages_table_wrapper'];
  }


  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): \Drupal\Core\Ajax\AjaxResponse
  {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box Items Have been re-ordered.', '#'.$this->getFormId());
  }

  /**
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $ajaxHelper = new FormHelper();

    // Get the new order
    $values = $form_state->getValue('pages_table');

    // Load the selected page
    $selected_page = Node::load($form_state->getValue('page_to_sort'));

    // Get its child pages
    $pages = $selected_page->get('field_child_pages')->getValue();

    // Get the new order
    $pages = $ajaxHelper->get_new_order($values,$pages);

    // Save the new order
    $selected_page->set('field_child_pages', array_values($pages));
    $selected_page->save();

    // Update parents
    $ajaxHelper->updateParent($form, $form_state);
  }
}
