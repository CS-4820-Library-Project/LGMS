<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Provides a form for adding or editing HTML content items.
 *
 * This form allows users to input and save HTML content as part of custom content
 * structures within the site, like guides or tutorials. It supports creating new
 * HTML items or editing existing ones, with features to save as draft or publish.
 */
class AddHTMLForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'add_html_form';
  }

  /**
   * Builds the HTML item add/edit form.
   *
   * @param array $form An associative array containing the structure of the form.
   * @param FormStateInterface $form_state The current state of the form.
   * @param mixed $ids Optional parameters for form construction, such as the
   *                   IDs of items to edit.
   *
   * @return array The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ids = null): array
  {
    // Set the prefix, suffix, and hidden fields
    $form_helper = new FormHelper();
    $form_helper->set_form_data($form,$ids, $this->getFormId());

    // In the case of editing an HTML, get the item
    $current_item = property_exists($ids, 'current_item')? Node::load($ids->current_item): null;
    $current_html = $current_item?->get('field_html_item')->entity;
    $edit = $current_item != null;

    // Title field
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item Title:'),
      '#required' => TRUE,
      '#default_value' => $edit? $current_html->getTitle(): '',
    ];

    // Body field
    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#required' => TRUE,
      '#default_value' => $edit? $current_html->get('field_text_box_item2')->value: '',
      '#format' => $edit ? $current_html->get('field_text_box_item2')->format : 'basic_html',
    ];

    $form_helper->draft_field($form,$form_state, $current_html, $current_item, $edit);

    // Create submit button and attach ajax method to it
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::submitAjax',
        'event' => 'click',
      ]
    ];

    return $form;
  }

  /**
   * AJAX form submission handler.
   *
   * Provides an AJAX callback for the form submission, enabling a more dynamic
   * and responsive user interface.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return AjaxResponse An AJAX response that can include
   *                                        commands like modal close and re-render.
   * @throws EntityMalformedException If the form submission encounters an
   *                                   entity related error.
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    $ajaxHelper = new FormHelper();
    $message = 'A HTML item has been added.';

    if ($form_state->getValue('current_item')){
      $message = 'A HTML item has been edited.';
    }

    return $ajaxHelper->submitModalAjax($form, $form_state, $message, '#'.$this->getFormId());
  }

  /**
   * Handles the submission of the HTML form.
   *
   * Processes the form values to either create a new HTML item node or update an
   * existing one. It ensures all HTML content is properly saved and linked within
   * the system.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @throws EntityStorageException If there's an issue saving the HTML content item.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $ajaxHelper = new FormHelper();

    // If we are creating a new node
    if($form_state->getValue('current_item') == null){
      // Create node
      $new_html = Node::create([
        'type' => 'guide_html_item',
        'title' => $form_state->getValue('title'),
        'field_text_box_item2' => [
          'value' => $form_state->getValue('body')['value'],
          'format' => $form_state->getValue('body')['format'],
        ],
        'status' => $form_state->getValue('published') == '0',
        'promote' => 0,
      ]);

      $new_html->save();

      // Create a link to it and add it to the box
      $ajaxHelper->create_link($new_html, $form_state->getValue('current_box'));
    } else {
      // Load link and it's content
      $current_item = $form_state->getValue('current_item');
      $current_item = Node::load($current_item);
      $html = $current_item->get('field_html_item')->entity;

      // update content
      $html->set('field_text_box_item2', [
        'value' => $form_state->getValue('body')['value'],
        'format' => $form_state->getValue('body')['format'],
      ]);
      $html->set('title', $form_state->getValue('title'));
      $html->save();

      // Update link
      $ajaxHelper->update_link($form, $form_state, $current_item);
    }

    // Update last change date for parents.
    $ajaxHelper->updateParent($form, $form_state);
  }
}
