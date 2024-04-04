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
    $form_helper = new FormHelper();

    $form_helper->set_prefix($form, $this->getFormId());

    $current_guide = \Drupal::request()->query->get('guide_id');

    if ($current_guide = Node::load($current_guide)) {

      $form['current_node'] = [
        '#type' => 'hidden',
        '#value' => $current_guide->id(),
      ];

      $options = $this->getPageList($current_guide->id());

      $form['select_page'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Page'),
        '#options' => $options,
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

      $form['include_sub_wrapper']['confirm_delete'] = [
        '#type' => 'checkbox',
        '#required' => true,
        '#states' => [
          'visible' => [
            ':input[name="select_page"]' => ['!value' => ''],
          ],
        ],
      ];


      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#button_type' => 'danger',
      ];

      $form['actions']['submit']['#ajax'] = [
        'callback' => '::submitAjax',
        'event' => 'click',
      ];
    } else {
      $form['error'] = [
        '#type' => 'markup',
        '#markup' => $this->t('The page could not be found.'),
      ];
    }

    return $form;
  }

  public function submitAjax(array &$form, FormStateInterface $form_state) {
    // Create an array of AJAX commands.
    $ajaxHelper = new FormHelper();
    $selected_page = $form_state->getValue('select_page');
    $pageTitle = Node::load($selected_page);
    if ($pageTitle && $pageTitle->bundle() === 'guide') {
      $message = 'Guide deleted successfully.';
    } else {
      $message = 'Page deleted successfully.';
    }

    return $ajaxHelper->submitModalAjax($form, $form_state, $message, '#'.$this->getFormId());
  }

  public function IncludeSubCallBack(array &$form, FormStateInterface $form_state) {
    $selected_page = $form_state->getValue('select_page');

    // Check if a page is selected and it's not the empty option.
    if (!empty($selected_page)) {
      $pageTitle = Node::load($selected_page);
      if ($pageTitle->bundle() != 'guide'){
        $form['include_sub_wrapper']['confirm_delete']['#title'] = t('<strong>Are you sure you want to delete the page @page_title?</strong> Deleting this page will remove it and its references permanently from the system!', ['@page_title' => $pageTitle->label()]);
      } else {
        $form['include_sub_wrapper']['confirm_delete']['#title'] = t('<strong>Are you sure you want to delete the guide @page_title?</strong> Deleting this guide will remove it permanently from the system!', ['@page_title' => $pageTitle->label()]);
      }

      // Load the selected page node to check its field_child_pages.
      if ($selected_page == 'top_level')
        $selected_page = $form_state->getValue('current_node');

      $page_node = Node::load($selected_page);
      if ($page_node) {
        $child_pages = $page_node->get('field_child_pages')->getValue();
        // If there are no child pages, disable the "Include Subpages" checkbox.
        if (empty($child_pages)) {
          $form['include_sub_wrapper']['include_sub']['#checked'] = FALSE;
          $form['include_sub_wrapper']['include_sub']['#attributes']['disabled'] = 'disabled';
        } else {
          // Ensure it is not disabled if there are child pages.
          unset($form['include_sub_wrapper']['include_sub']['#attributes']['disabled']);

          if($selected_page == $form_state->getValue('current_node')){
            $form['include_sub_wrapper']['include_sub']['#title'] = 'All Page and subPages of this Guide will be deleted.';
            $form['include_sub_wrapper']['include_sub']['#required'] = true;
          }
        }
      }
    } else {
      // If no page is selected, ensure the "Include Subpages" checkbox is not disabled.
      unset($form['include_sub_wrapper']['include_sub']['#attributes']['disabled']);
    }

    // Return parts of the form that need to be re-rendered.
    // Ensure you return both the 'position_wrapper' and 'include_sub' elements if they both need updating.
    return $form['include_sub_wrapper'];
  }


  public function submitForm(array &$form, FormStateInterface $form_state){
    $selected_page = $form_state->getValue('select_page');
    $delete_sub = $form_state->getValue('include_sub') == '1';
    $helper = new FormHelper();

    if ($selected_page == 'top_level'){
      $selected_page = $form_state->getValue('current_node');
    }

    $selected_page = Node::load($selected_page);

    $helper->deletePages($selected_page, $delete_sub);
  }

  public function getPageList($guide_id) {
    $options = [];

    $options['Guide'][$guide_id] = t('Entire Guide');

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
