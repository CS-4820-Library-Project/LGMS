<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class AddHTMLForm extends FormBase {

  public function getFormId() {
    return 'add_html_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $ids = null) {
    // Set the prefix, suffix, and hidden fields
    $form_helper = new FormHelper();
    $form_helper->set_form_data($form,$ids);

    // In the case of editing an HTML, get the item
    $current_item = Node::load($ids->current_item);
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
      //'#after_build' => [[get_class($this), 'hideTextFormatHelpText'],],
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
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'an HTML field has been added.');
  }

  public static function hideTextFormatHelpText(array $element, FormStateInterface $form_state) {
    if (isset($element['format']['help'])) {
      $element['format']['help']['#access'] = FALSE;
    }
    if (isset($element['format']['guidelines'])) {
      $element['format']['guidelines']['#access'] = FALSE;
    }
    if (isset($element['format']['#attributes']['class'])) {
      unset($element['format']['#attributes']['class']);
    }
    return $element;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    if($form_state->getValue('current_item') == null){
      $current_box = $form_state->getValue('current_box');
      $current_box = Node::load($current_box);

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

      $new_item = Node::create([
        'type' => 'guide_item',
        'title' => $form_state->getValue('title'),
        'field_html_item' => $new_html,
        'field_parent_box' => $current_box,
        'status' => $form_state->getValue('published') == '0',
      ]);

      $new_item->save();

      $new_html->set('field_parent_item',$new_item);
      $new_html->save();

      $boxList = $current_box->get('field_box_items')->getValue();
      $boxList[] = ['target_id' => $new_item->id()];

      $current_box->set('field_box_items', $boxList);
      $current_box->save();
    } else {
      $current_item = $form_state->getValue('current_item');
      $current_item = Node::load($current_item);

      $html = $current_item->get('field_html_item')->entity;

      $html->set('field_text_box_item2', [
        'value' => $form_state->getValue('body')['value'],
        'format' => $form_state->getValue('body')['format'],
      ]);
      $html->set('title', $form_state->getValue('title'));
      $html->save();

      $current_item->set('title', $form_state->getValue('title'));
      $current_item->set('status', $form_state->getValue('published') == '0');
      $current_item->set('changed', \Drupal::time()->getRequestTime());
      $current_item->save();
    }

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
