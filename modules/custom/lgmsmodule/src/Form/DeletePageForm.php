<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;

/**
 * Form handler for deleting a single page or an entire guide.
 *
 * Presents a confirmation form to the user for deleting a selected page
 * from a guide or the guide itself, including the option to delete all
 * subpages and associated content. It dynamically adjusts the confirmation
 * message based on whether a guide or a single page is being deleted.
 */
class DeletePageForm extends FormBase{

  /**
   * Checks if the user can edit their own article.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Drupal\Core\Session\AccountInterface $account
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(Request $request, AccountInterface $account) {
    $nid = $request->query->get('guide_id');
    $node = Node::load($nid);

    if ($node && $node->getType() == 'guide' && $node->access('delete')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'delete_page_form';
  }

  /**
   * Constructs the delete page form.
   *
   * @param array $form An associative array containing the structure of the form.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return array The form structure.
   */
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

    $selected_page = $form_state->getValue('select_page');
    $selected_page = Node::load($selected_page);

    $form['update_wrapper']['confirm_delete'] = [
      '#type' => 'checkbox',
      '#title' => $selected_page? $selected_page->bundle() == 'guide'
        ? $this->t('<strong>Are you sure you want to delete the guide @page_title?</strong>
                   This will remove it and any Pages and boxes it directly owns and the boxes of it\'s pages
                   (but not links to pages or boxes owned by other guides or pages, nor any content items).
                   ', ['@page_title' => $selected_page->label()])
        : $this->t(
          '<strong>Are you sure you want to delete the page @page_title?</strong>
                   This will remove it and any boxes it directly owns (but not links to boxes owned by other pages,
                   nor any content items).', ['@page_title' => $selected_page->label()])
        : '',
      '#states' => [
        'visible' => [
          ':input[name="select_page"]' => ['!value' => ''],
        ],
      ],
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#required' => True,
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
   * Submits the form using AJAX, providing immediate user feedback.
   *
   * Handles the AJAX form submission, allowing for a smoother user experience
   * by providing feedback directly in the modal dialog without requiring a page
   * refresh. It constructs an appropriate success message based on the content
   * type that was deleted.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   *
   * @return AjaxResponse The AJAX response for the form submission.
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
      $form_state->setValue('current_node', $form_state->getValue('guide_id'));
    }

    return $ajaxHelper->submitModalAjax($form, $form_state, $message, '#'.$this->getFormId());
  }

  /**
   * AJAX callback for dynamically updating the form based on page selection.
   *
   * Updates the form's confirmation message and visibility of certain form elements
   * based on the selected page. Adjusts the message for guide deletion or
   * single page deletion and handles the presence of subpages.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return array The updated portion of the form.
   */
  public function PageSelectedCallBack(array &$form, FormStateInterface $form_state): array
  {
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

  /**
   * Processes the deletion of the selected page or guide upon form submission.
   *
   * Executes the deletion logic for the selected content, removing it from the
   * system. It handles the deletion of a single page, all its subpages, and
   * associated content if specified, or an entire guide and its structure.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   */
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
