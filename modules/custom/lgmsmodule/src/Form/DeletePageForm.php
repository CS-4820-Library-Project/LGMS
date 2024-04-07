<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;


class DeletePageForm extends FormBase{
  public function getFormId(): string
  {
    return 'delete_page_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form_helper = new FormHelper();

    // Get the data from the URL
    $ids = (object) [
      'guide_id' => \Drupal::request()->query->get('guide_id'),
    ];

    // Set the prefix, suffix, and hidden fields
    $form_helper->set_form_data($form, $ids, $this->getFormId());

    // Select Page field
    $form['select_page'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Page'),
      '#options' => $form_helper->get_pages_options($ids->guide_id),
      '#empty_option' => $this->t('- Select a Page -'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::PageSelectedCallBack',
        'wrapper' => 'update-wrapper',
        'event' => 'change',
      ],
    ];

    // Wrapper to update when the page is selected
    $form['update_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'update-wrapper'],
    ];

    $form['update_wrapper']['include_sub'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete All it\'s subPages as well.'),
      '#states' => [
        'visible' => [
          ':input[name="select_page"]' => ['!value' => ''],
        ],
      ],
    ];

    $form['update_wrapper']['confirm_delete'] = [
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
      '#ajax' => [
        'callback' => '::submitAjax',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    $ajaxHelper = new FormHelper();

    // Load the selected node
    $selected_page = $form_state->getValue('select_page');
    $pageTitle = Node::load($selected_page);

    // Check if the node is a guide or page and update the message to be sent
    if ($pageTitle && $pageTitle->bundle() === 'guide') {
      $message = 'Guide deleted successfully.';
    } else {
      $message = 'Page deleted successfully.';
    }

    return $ajaxHelper->submitModalAjax($form, $form_state, $message, '#'.$this->getFormId());
  }

  public function PageSelectedCallBack(array &$form, FormStateInterface $form_state) {
    $selected_page = $form_state->getValue('select_page');

    // Check if a page is selected and it's not the empty option.
    if (!empty($selected_page)) {
      $page = Node::load($selected_page);

      if ($page) {
        if ($page->bundle() != 'guide'){
          $form['update_wrapper']['confirm_delete']['#title'] = t(
            '<strong>Are you sure you want to delete the page @page_title?</strong>
                   This will remove it and any boxes it directly owns (but not links to boxes owned by other pages,
                   nor any content items).', ['@page_title' => $page->label()]
          );

        } else {
          $form['update_wrapper']['confirm_delete']['#title'] = t(
            '<strong>Are you sure you want to delete the guide @page_title?</strong>
                   This will remove it and any Pages and boxes it directly owns and the boxes of it\'s pages
                   (but not links to pages or boxes owned by other guides or pages, nor any content items).
                   ', ['@page_title' => $page->label()]);
        }

        // Get the child pages
        $child_pages = $page->get('field_child_pages')->getValue();

        // If there are no child pages, disable the "Include Subpages" checkbox.
        if (empty($child_pages)) {
          $form['update_wrapper']['include_sub']['#checked'] = FALSE;
          $form['update_wrapper']['include_sub']['#attributes']['disabled'] = 'disabled';
        } else {
          if($page->bundle() == 'guide'){
            // remove include sub checkbox if it's the guide
            unset($form['update_wrapper']['include_sub']);
          } else {
            // Ensure it is not disabled if there are child pages.
            unset($form['update_wrapper']['include_sub']['#attributes']['disabled']);
          }
        }
      }
    }

    return $form['update_wrapper'];
  }


  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $ajaxHelper = new FormHelper();

    // Get form values
    $selected_page = $form_state->getValue('select_page');
    $delete_sub = $form_state->getValue('include_sub') == '1';

    // Load Page
    $page = Node::load($selected_page);
    if ($page->bundle() == 'guide'){
      $delete_sub = true;
    }

    // Delete page
    $ajaxHelper->deletePages($page, $delete_sub);
  }
}
