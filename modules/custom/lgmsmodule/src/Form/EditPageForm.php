<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class EditPageForm extends FormBase {

  public function getFormId() {
    return 'edit_page_form';
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
    if ($current_guide) {
      $current_guide = Node::load($current_guide);
    }



    if ($current_guide) {
      $form['current_node'] = [
        '#type' => 'hidden',
        '#value' => $current_guide->id(),
      ];

      $form['select_page'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Page'),
        '#options' => $this->getPageList($current_guide->id(), true),
        '#empty_option' => $this->t('- Select a Page -'),
        '#validated' => TRUE,
        '#required' => TRUE,
        '#ajax' => [
          'callback' => '::selectPageCallBack',
          'wrapper' => 'update-wrapper',
          'event' => 'change',
        ],
      ];

      $form['update_wrapper'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'update-wrapper'],
      ];

      // Title field pre-filled with the existing title.
      $form['update_wrapper']['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Page Title'),
        '#required' => TRUE,
      ];

      // Description field pre-filled with the existing body.
      $form['update_wrapper']['hide_description'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Hide description'),
        '#ajax' => [
          'callback' => '::hideDescriptionCallback',
          'wrapper' => 'update-wrapper', // This should be the ID of the element you want to replace or update, you can adjust as needed.
          'event' => 'change',
        ],
      ];

      // Description field with state controlled by hide_description checkbox.
      $form['update_wrapper']['description'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Description'),
        '#states' => [
          'invisible' => [
            ':input[name="hide_description"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['update_wrapper']['position'] = [
        '#type' => 'select',
        '#title' => $this->t('Position'),
        '#options' => $this->getPageList($current_guide->id(), false),
        '#required' => TRUE,
      ];

      /**Follow This**/
      $selected = !empty($form_state->getValue('select_page')) ? $form_state->getValue('select_page') : '';

      if($selected != ''){
        $selected = Node::load($selected);
        if((!$selected->hasField('field_reference_node') || $selected->get('field_reference_node')->isEmpty())){
          $form['update_wrapper']['description']['#value'] = $selected->get('field_description')->value;
        } else {
          $form['update_wrapper']['description']['#attributes']['disabled'] = true;
        }
        if($selected->hasField('field_parent_guide')) {
          //$form['update_wrapper']['position']['#value'] = $selected->get('field_parent_guide')->entity->id();
        }
      }

      $form['update_wrapper']['draft_mode'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Draft Mode'),
        '#description' => $this->t('Check this box if the page is still in draft mode.'),
      ];


      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Edit'),
        '#button_type' => 'primary',
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

  public function hideDescriptionCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $response->addCommand(new ReplaceCommand('#update-wrapper', $form['update_wrapper']));

    return $response;
  }


  public function submitAjax(array &$form, FormStateInterface $form_state) {
    // Create an array of AJAX commands.
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Page updated successfully.', '#'.$this->getFormId());
  }


  public function selectPageCallBack(array &$form, FormStateInterface $form_state) {
    $selected_page = $form_state->getValue('select_page');

    // Check if a page is selected and it's not the empty option.
    if (!empty($selected_page)) {
      // Load the selected page node to check its field_child_pages.
      $page_node = Node::load($selected_page);
      if ($page_node && (!$page_node->hasField('field_reference_node') || $page_node->get('field_reference_node')->isEmpty())) {
        $form['update_wrapper']['title']['#value'] = $page_node->label();
        $form['update_wrapper']['hide_description']['#checked'] = $page_node->get('field_hide_description')->value;
        $form['update_wrapper']['draft_mode']['#checked'] = !$page_node->isPublished();


        $child_pages = $page_node->get('field_child_pages')->referencedEntities();

        if (!empty($child_pages)) {
          $guide = $form_state->getValue('current_node');
          $guide = Node::load($guide);
          $form['update_wrapper']['position']['#options'] = ['Guide' => [$guide->id() => $guide->label()]];
        } else {
          if($page_node->hasField('field_parent_guide')) {
            $form['update_wrapper']['position']['#value'] = $page_node->get('field_parent_guide')->entity->id();
          }
        }
      } else {
        $form['update_wrapper']['title']['#value'] = 'This is Just a Reference and can not be Edited';
        $form['update_wrapper']['title']['#attributes']['disabled'] = true;
        $form['update_wrapper']['hide_description']['#attributes']['disabled'] = true;
        $form['update_wrapper']['draft_mode']['#attributes']['disabled'] = true;
        $form['update_wrapper']['position']['#attributes']['disabled'] = true;
      }
    } else {

    }

    return $form['update_wrapper'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_page = $form_state->getValue('select_page');
    $selected_page = Node::load($selected_page);

    $selected_page->setTitle($form_state->getValue('title'));
    $selected_page->set('field_hide_description', $form_state->getValue('select_page'));
    $selected_page->set('field_description', [
      'value' => $form_state->getValue('description')['value'],
      'format' => $form_state->getValue('description')['format'],
    ]);
    $form_state->getValue('draft_mode') == '0'? $selected_page->setPublished(): $selected_page->setUnpublished();


    if($selected_page->hasField('field_parent_guide')){
      $parent = $selected_page->get('field_parent_guide')->entity;

      $child_pages = $parent->get('field_child_pages')->getValue();

      $child_pages = array_filter($child_pages, function ($page) use ($selected_page) {
        return $page['target_id'] != $selected_page->id();
      });

      $parent->set('field_child_pages', $child_pages);
      $parent->save();

      $new_parent = Node::load($form_state->getValue('position'));
      $selected_page->set('field_parent_guide', $new_parent);
      $selected_page->save();

      $page_list = $new_parent->get('field_child_pages')->getValue();
      $page_list[] = ['target_id' => $selected_page->id()];

      $new_parent->set('field_child_pages', $page_list);
      $new_parent->set('changed', \Drupal::time()->getRequestTime());
      $new_parent->save();
    }


    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }

  public function getPageList($guide_id, $include_sub) {
    $options = [];
    $guide = Node::load($guide_id);


    // Check if the guide has been loaded and has the field_child_pages field.
    if ($guide && $guide->hasField('field_child_pages')) {
      // Get the array of child page IDs from the guide.
      $child_pages = $guide->get('field_child_pages')->referencedEntities();

      if (!empty($child_pages)) {
        // Group label for child pages.
        $group_label = $include_sub? 'Pages' : 'Subpage Of';

        // Initialize the group if it's not set.
        if (!isset($options[$group_label])) {
          $options[$group_label] = [];
        }

        // Create options array from the child pages.
        foreach ($child_pages as $child_page) {
          if ($child_page->get('field_parent_guide')->entity->id() == $guide_id) {
            $options[$group_label][$child_page->id()] = $child_page->label();

            // Check if the child page has its own subpages.
            if ($child_page->hasField('field_child_pages') && $include_sub) {
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
