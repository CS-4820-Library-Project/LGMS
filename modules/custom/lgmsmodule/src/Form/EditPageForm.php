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
    $form_helper = new FormHelper();
    $form_helper->set_prefix($form,  $this->getFormId());

    $current_guide = \Drupal::request()->query->get('guide_id');
    if ($current_guide) {
      $current_guide = Node::load($current_guide);
    }

    if ($current_guide) {
      $form['select_page'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Page'),
        '#options' => $this->getPageList($form_state, $current_guide->id(), true),
        '#empty_option' => $this->t('- Select a Page -'),
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

      $selected = !empty($form_state->getValue('select_page')) ? $form_state->getValue('select_page') : '';

      if($selected != '') {
        $selected_node = Node::load($selected);
        $reference = !$selected_node->get('field_reference_node')->isEmpty();

        $form['update_wrapper']['current_node'] = [
          '#type' => 'hidden',
          '#value' => $selected_node->id(),
        ];

        // Title field pre-filled with the existing title.
        $form['update_wrapper']['title'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Page Title'),
          '#default_value' => $reference? t('This is Just a Reference and can not be Edited') : $selected_node->label(),
          '#required' => !$reference,
          '#disabled' => $reference,
        ];

        // Description field pre-filled with the existing body.
        $form['update_wrapper']['hide_description'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Hide description'),
          '#default_value' => $selected_node->get('field_hide_description')->value,
          '#disabled' => $reference,
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
            'required' => [
              ':input[name="hide_description"]' => ['checked' => False],
            ],
          ],
          '#default_value' => $selected_node->get('field_description')->value,
          '#disabled' => $reference,
        ];

        $form['update_wrapper']['position'] = [
          '#type' => 'select',
          '#title' => $this->t('Position'),
          '#options' => $this->getPageList($form_state, $current_guide->id(), false),
          '#value' => !$selected_node->get('field_child_pages')->isEmpty()? $current_guide->id() : $selected_node->get('field_parent_guide')->entity->id(),
          '#disabled' => !$selected_node->get('field_child_pages')->isEmpty(),
          '#required' => TRUE,
        ];

        $disable = !$selected_node->get('field_reference_node')->isEmpty() && !$selected_node->get('field_reference_node')->entity->isPublished();

        $form['update_wrapper']['draft_mode'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Draft Mode'),
          '#description' => $disable? $this->t('The referenced page is unPublished. Publish it to be able to update this Page.') : $this->t('Check this box if the page is still in draft mode.'),
          '#default_value' => !$selected_node->isPublished(),
          '#disabled' => $disable,
        ];
      }
    } else {
      $form['error'] = [
        '#type' => 'markup',
        '#markup' => $this->t('The page could not be found.'),
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Edit'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::submitAjax',
        'event' => 'click',
      ],
    ];

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
    return $form['update_wrapper'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_page = $form_state->getValue('select_page');
    $selected_page = Node::load($selected_page);

    if (!$selected_page->get('field_reference_node')->isEmpty()){
      $form_state->getValue('draft_mode') == '0'? $selected_page->setPublished(): $selected_page->setUnpublished();
      $selected_page->save();
    } else {
      $selected_page->setTitle($form_state->getValue('title'));
      $selected_page->set('field_hide_description', $form_state->getValue('hide_description') == '1');
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
    }

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }

  public function getPageList(FormStateInterface $form_state, $guide_id, $include_sub) {
    $options = [];
    $guide = Node::load($guide_id);


    // Check if the guide has been loaded and has the field_child_pages field.
    if ($guide && $guide->hasField('field_child_pages')) {
      // Get the array of child page IDs from the guide.
      $child_pages = $guide->get('field_child_pages')->referencedEntities();

      if (!empty($child_pages)) {
        // Group label for child pages.
        if (!$include_sub){
          $options['Page Level'][$guide_id] = t('Page Level');
        }

        $group_label = $include_sub? 'Pages' : 'Subpage Of';

        // Initialize the group if it's not set.
        if (!isset($options[$group_label])) {
          $options[$group_label] = [];
        }

        // Create options array from the child pages.
        foreach ($child_pages as $child_page) {
          if (!$include_sub && $child_page->id() == $form_state->getValue('select_page')){
            continue;
          }

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
