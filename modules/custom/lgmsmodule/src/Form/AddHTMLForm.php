<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class AddHTMLForm extends FormBase {

  public function getFormId(): string
  {
    return 'add_html_form';
  }

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

    // Draft mode Field
    $form['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Draft mode:'),
      '#description' => $edit && !$current_html->isPublished() ? $this->t('Please publish the original node') : $this->t('Un-check this box to publish.'),
      '#default_value' => $edit ? $current_item->isPublished() == '0': 0,
      '#disabled' => $edit && !$current_html->isPublished(),
    ];

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
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): \Drupal\Core\Ajax\AjaxResponse
  {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'an HTML field has been added.', '#'.$this->getFormId());
  }

  /**
   * @throws EntityStorageException
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
