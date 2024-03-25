<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class ReuseHTMLItemForm extends FormBase {

  public function getFormId() {
    return 'reuse_html_item_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $ids = null) {
    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    $form['current_box_content'] = [
      '#type' => 'hidden',
      '#value' => $ids->current_box_content,
    ];


    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $ids->current_node,
    ];


    $form['box'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('HTML Item Name'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['guide_html_item'],
      ],
      '#required' => TRUE,
    ];

    $form['reference'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<Strong>Reference:</Strong> By selecting this, a reference of the HTML item will be created. it will be un-editable from this box'),
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('New Title:'),
      '#states' => [
        'invisible' => [
          ':input[name="reference"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['#validate'][] = '::validateFields';

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

  public function validateFields(array &$form, FormStateInterface $form_state) {
    $reference = $form_state->getValue('reference');
    $title = $form_state->getValue('title');
    if (!$reference && empty($title)) {
      $form_state->setErrorByName('title', $this->t('Title: field is required.'));
    }
  }

  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'HTML Item created successfully.');
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
    $current_box_content = $form_state->getValue('current_box_content');
    $current_box_content = Node::load($current_box_content);


    $html = Node::load($form_state->getValue('box'));
    $item = $html->get('field_parent_item')->entity;

    if(!$form_state->getValue('reference')){
      $new_html = $html->createDuplicate();
      $new_item = $item->createDuplicate();

      $new_html->set('field_parent_item', $new_item);
      $new_html->set('title', $form_state->getValue('title'));
      $new_html->save();

      $new_item->set('field_parent_box_content', $current_box_content);
      $new_item->set('title', $form_state->getValue('title'));
      $new_item->set('field_html_item', $new_html);

      $new_item->save();

      $item = $new_item;
    }

    $boxList = $current_box_content->get('field_box_items')->getValue();
    $boxList[] = ['target_id' => $item->id()];

    $current_box_content->set('field_box_items', $boxList);
    $current_box_content->save();

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
