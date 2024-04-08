<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Provides a form to reuse an HTML item.
 *
 * This form allows users to select an existing HTML item to duplicate or reference
 * within another context, such as a different box or page.
 */
class ReuseHTMLItemForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'reuse_html_item_form';
  }

  /**
   * Builds the reuse HTML item form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   * @param array|null $ids
   *   (optional) Additional identifiers for form construction.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ids = null): array
  {
    // Set the prefix, suffix, and hidden fields
    $form_helper = new FormHelper();
    $form_helper->set_form_data($form,$ids, $this->getFormId());

    // Select element for HTML items.
    $form['html_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Select HTML Item'),
      '#options' => $form_helper->get_item_options('guide_html_item'),
      '#empty_option' => $this->t('- Select an HTML Item -'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::htmlItemSelectedAjaxCallback',
        'wrapper' => 'update-wrapper',
        'event' => 'change',
      ],
    ];

    // Checkbox to indicate if the item is a link.
    $form['reference'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Link:</strong> By selecting this, a link to the HTML item will be created. It will be un-editable from this box.'),
    ];

    // Container to dynamically update based on AJAX callback.
    $form['update_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'update-wrapper'],
    ];

    // Pre-fill form fields if an HTML item is selected.
    $this->prefillSelectedHtmlItem($form, $form_state);

    // Validation and submission handlers.
    $form['#validate'][] = '::validateFields';
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

  /**
   * Pre-fills the selected HTML item fields if one is selected.
   *
   * @param array $form
   *   The form definition array.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   */
  private function prefillSelectedHtmlItem(array &$form, FormStateInterface $form_state): void
  {
    // Get the selected html
    $selected = $form_state->getValue('html_select');

    if (!empty($selected)) {
      // Load the HTML item
      $selected_node = Node::load($selected);

      if ($selected_node) {
        $reference = $form_state->getValue('reference');

        // Add the fields for the HTML if the user wants a copy
        $form['update_wrapper']['title'] = [
          '#type' => 'textfield',
          '#title' => $this->t('New Title:'),
          '#default_value' => $reference ? $this->t('This is just a Link and cannot be edited.') : $selected_node->label(),
          '#required' => !$reference,
          '#disabled' => $reference,
          '#states' => [
            'invisible' => [':input[name="reference"]' => ['checked' => TRUE]],
          ],
        ];

        $form['update_wrapper']['body'] = [
          '#type' => 'text_format',
          '#title' => $this->t('HTML Body'),
          '#default_value' => $selected_node->get('field_text_box_item2')->value,
          '#format' => $selected_node->get('field_text_box_item2')->format,
          '#disabled' => $reference,
          '#states' => [
            'invisible' => [':input[name="reference"]' => ['checked' => TRUE]],
            'required' => [':input[name="reference"]' => ['checked' => FALSE]],
          ],
        ];
      }
    }
  }

  /**
   * Validates the form submission.
   *
   * Ensures that a title is provided when not creating a reference.
   *
   * @param array &$form
   *   The form render array.
   * @param FormStateInterface $form_state
   *   The form state.
   */

  public function validateFields(array &$form, FormStateInterface $form_state): void
  {
    // Throw error if title field is not filled
    if (!$form_state->getValue('reference') && empty($form_state->getValue('title'))) {
      $form_state->setErrorByName('title', $this->t('Title: field is required.'));
    }
  }

  /**
   * Handles AJAX callback for HTML item selection.
   *
   * Updates the form state based on the selected HTML item.
   *
   * @param array &$form
   *   The form render array.
   * @param FormStateInterface $form_state
   *   The form state.
   *
   * @return AjaxResponse
   *   The AJAX response.
   */
  public function htmlItemSelectedAjaxCallback(array &$form, FormStateInterface $form_state)
  {
    // Load the selected html
    $selected = $form_state->getValue('html_select');
    $selected_node = Node::load($selected);

    // update the title and body fields
    if ($selected_node){
      $form['update_wrapper']['title']['#value'] = $selected_node->label();
      $form['update_wrapper']['body']['value']['#value'] = $selected_node->get('field_text_box_item2')->value;
    }

    return $form['update_wrapper'];
  }

  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'HTML Item created successfully.', '#'.$this->getFormId());
  }

  /**
   * Handles form submission.
   *
   * Duplicates or references the selected HTML item based on user input.
   *
   * @param array &$form
   *   The form render array.
   * @param FormStateInterface $form_state
   *   The form state.
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $ajaxHelper = new FormHelper();

    // Get the box to add the html item to
    $current_box_id = $form_state->getValue('current_box');
    $current_box = Node::load($current_box_id);

    // Get the selected html
    $html_id = $form_state->getValue('html_select');
    $html = Node::load($html_id);

    // Get the link to the selected html
    $item = $html->get('field_parent_item')->entity;

    // creating a copy
    if(!$form_state->getValue('reference')){
      // create a copy of both the link and html item
      $new_html = $html->createDuplicate();
      $new_item = $item->createDuplicate();

      // Update html fields
      $new_html->set('field_parent_item', $new_item);
      $new_html->set('title', $form_state->getValue('title'));
      $new_html->set('field_text_box_item2', $form_state->getValue('body'));
      $new_html->set('promote', 0);
      $new_html->save();

      // Update link fields
      $new_item->set('field_parent_box', $current_box);
      $new_item->set('title', $form_state->getValue('title'));
      $new_item->set('field_html_item', $new_html);
      $new_item->set('promote', 0);
      $new_item->save();

    } else {
      // Create a new link
      $new_item = $item->createDuplicate();
      $new_item->set('field_lgms_reference', TRUE);
      $new_item->set('promote', 0);
      $new_item->save();
    }

    $item = $new_item;

    // Updating the box list with the new item
    $ajaxHelper->add_child($current_box, $item,'field_box_items');

    // Update link
    $ajaxHelper->updateParent($form, $form_state);
  }
}
