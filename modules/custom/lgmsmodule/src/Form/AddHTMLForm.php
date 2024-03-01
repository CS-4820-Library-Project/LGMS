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

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    $current_box = \Drupal::request()->query->get('current_box');
    $form['current_box'] = [
      '#type' => 'hidden',
      '#value' => $current_box,
    ];

    $current_node = \Drupal::request()->query->get('current_node');
    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $current_node,
    ];

    $current_item = \Drupal::request()->query->get('current_item');
    $edit = false;

    if(!empty($current_item)){
      $form['current_item'] = [
        '#type' => 'hidden',
        '#value' => $current_item,
      ];

      $edit = true;
      $current_item = Node::load($current_item);
    }


    $form['edit'] = [
      '#type' => 'hidden',
      '#value' => $edit,
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item Title:'),
      '#required' => TRUE,
      '#default_value' => $edit? $current_item->getTitle(): '',
    ];


    // Body field
    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#after_build' => [[get_class($this), 'hideTextFormatHelpText'],],
      '#required' => TRUE,
      '#default_value' => $edit? $current_item->get('field_text_box_item')->value: '',
    ];


    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    $form['actions']['submit']['#ajax'] = [
      'callback' => '::submitAjax',
      'event' => 'click',
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
    $edit = $form_state->getValue('edit');

    if($edit == '0'){
      $current_box = $form_state->getValue('current_box');
      $current_box = Node::load($current_box);

      $new_node = Node::create([
        'type' => 'guide_item',
        'title' => $form_state->getValue('title'),
        'field_text_box_item' => [
          'value' => $form_state->getValue('body')['value'],
          'format' => $form_state->getValue('body')['format'],
        ],
      ]);

      $new_node->save();

      $boxList = $current_box->get('field_box_items')->getValue();
      $boxList[] = ['target_id' => $new_node->id()];

      $current_box->set('field_box_items', $boxList);
      $current_box->save();
    } else {
      $current_item = $form_state->getValue('current_item');
      $current_item = Node::load($current_item);

      $current_item->set('field_text_box_item', [
        'value' => $form_state->getValue('body')['value'],
        'format' => $form_state->getValue('body')['format'],
      ]);
      $current_item->set('title', $form_state->getValue('title'));

      $current_item->save();
    }

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
