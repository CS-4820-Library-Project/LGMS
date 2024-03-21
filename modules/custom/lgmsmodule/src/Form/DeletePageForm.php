<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class DeletePageForm extends FormBase
{

  public function getFormId()
  {
    return 'delete_page_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    $current_guide = \Drupal::request()->query->get('guide_id');
    if ($current_guide) {
      $current_guide = Node::load($current_guide);
    }



    if ($current_guide) {
      $form['current_page'] = [
        '#type' => 'hidden',
        '#value' => $current_guide->id(),
      ];

      $form['select_page'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Page'),
        '#options' => $this->getPageList($current_guide->id()),
        '#empty_option' => $this->t('- Select a Page -'),
        '#validated' => TRUE,
        '#required' => TRUE,
        '#ajax' => [
          'callback' => '::IncludeSubCallBack',
          'wrapper' => 'include-sub-wrapper',
          'event' => 'change',
        ],
      ];

      $form['include_sub_wrapper'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'include-sub-wrapper'],
      ];

      $form['include_sub_wrapper']['include_sub'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Delete All it\'s subPages as well.'),
      ];

      $title = $this->t('<strong>Are you sure you want to delete this page?</strong>
                                Deleting this page will remove it permanently from the system!');
      $form['include_sub_wrapper']['confirm_delete'] = [
        '#type' => 'checkbox',
        '#title' => $title,
        '#required' => true,
      ];


      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#button_type' => 'danger',
      ];

    } else {
      $form['error'] = [
        '#type' => 'markup',
        '#markup' => $this->t('The page could not be found.'),
      ];
    }

    return $form;
  }

  public function IncludeSubCallBack(array &$form, FormStateInterface $form_state) {
    $selected_page = $form_state->getValue('select_page');

    // Check if a page is selected and it's not the empty option.
    if (!empty($selected_page)) {
      // Load the selected page node to check its field_child_pages.
      if ($selected_page == 'top_level')
        $selected_page = $form_state->getValue('current_page');

      $page_node = Node::load($selected_page);
      if ($page_node) {
        $child_pages = $page_node->get('field_child_pages')->getValue();
        // If there are no child pages, disable the "Include Subpages" checkbox.
        if (empty($child_pages)) {
          $form['include_sub_wrapper']['include_sub']['#checked'] = FALSE;
          $form['include_sub_wrapper']['include_sub']['#attributes']['disabled'] = 'disabled';

          //unset($form['position_wrapper']['position']['#attributes']['disabled']);
        } else {
          // Ensure it is not disabled if there are child pages.
          unset($form['include_sub_wrapper']['include_sub']['#attributes']['disabled']);

          if($selected_page == $form_state->getValue('current_page')){
            $form['include_sub_wrapper']['include_sub']['#title'] = 'All Page and subPages of this Guide will be deleted.';
            $form['include_sub_wrapper']['include_sub']['#required'] = true;
          }
        }
      }
    } else {
      // If no page is selected, ensure the "Include Subpages" checkbox is not disabled.
      unset($form['include_sub_wrapper']['include_sub']['#attributes']['disabled']);
      //unset($form['position_wrapper']['position']['#attributes']['disabled']);
    }

    // Return parts of the form that need to be re-rendered.
    // Ensure you return both the 'position_wrapper' and 'include_sub' elements if they both need updating.
    return $form['include_sub_wrapper'];
  }


  public function submitForm(array &$form, FormStateInterface $form_state){
    $selected_page = $form_state->getValue('select_page');
    $delete_sub = $form_state->getValue('include_sub') == '1';

    if ($selected_page == 'top_level'){
      $selected_page = $form_state->getValue('current_page');
    }

    $selected_page = Node::load($selected_page);

    $this->deletePages($selected_page, $delete_sub);
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

  public function deleteBoxes($parent){
    $boxes = $parent->get('field_child_boxes')->referencedEntities();

    foreach($boxes as $box){
      if($parent->id() == $box->get('field_parent_node')->entity->id()){
        $this->deleteItems($box);

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

  public function deleteItems($parent){
    $items = $parent->get('field_box_items')->referencedEntities();

    foreach($items as $item){
      if($parent->id() == $item->get('field_parent_box')->entity->id()){

        $query = \Drupal::entityQuery('node')
          ->condition('type', 'guide_box')
          ->condition('field_box_items', $item->id())
          ->accessCheck(TRUE);
        $result = $query->execute();

        foreach ($result as $box){
          $box = Node::load($box);
          $child_items = $box->get('field_box_items')->getValue();

          $child_items = array_filter($child_items, function ($box) use ($item) {
            return $box['target_id'] != $item->id();
          });

          $box->set('field_box_items', $child_items);
          $box->save();
        }

        $item->get('field_html_item')->entity?->delete();
        $item->get('field_book_item')->entity?->delete();

        $item?->delete();
      }
    }

  }

  public function getPageList($guide_id) {
    $options = [];

    $options['Guide']['top_level'] = t('Entire Guide');

    // Load the guide entity.
    $guide = Node::load($guide_id);

    // Check if the guide has been loaded and has the field_child_pages field.
    if ($guide && $guide->hasField('field_child_pages')) {
      // Get the array of child page IDs from the guide.
      $child_pages = $guide->get('field_child_pages')->referencedEntities();

      if (!empty($child_pages)) {
        // Group label for child pages.
        $group_label = 'Pages';

        // Initialize the group if it's not set.
        if (!isset($options[$group_label])) {
          $options[$group_label] = [];
        }

        // Create options array from the child pages.
        foreach ($child_pages as $child_page) {
          $options[$group_label][$child_page->id()] = $child_page->label();
          if ($child_page->get('field_parent_guide')->entity->id() == $guide_id) {

            // Check if the child page has its own subpages.
            if ($child_page->hasField('field_child_pages')) {
              $subpages_ids = array_column($child_page->get('field_child_pages')->getValue(), 'target_id');
              $subpages = !empty($subpages_ids) ? Node::loadMultiple($subpages_ids) : [];

              // Label each subpage with the parent page title.
              foreach ($subpages as $subpage) {
                $label = '— ' .  $subpage->getTitle();
                $options[$group_label][$subpage->id()] = $label;
              }
            }
          }
        }
      }
    }

    // Return the options array with the 'Top Level' and the grouped child pages.
    return $options;
  }
}
