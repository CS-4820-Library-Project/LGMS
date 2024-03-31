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
    // Define form wrapper and status messages.
    $form['#prefix'] = '<div id="' . $this->getFormId() . '">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    // Hidden fields to store current context.
    $form['current_box'] = [
      '#type' => 'hidden',
      '#value' => $ids->current_box,
    ];
    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $ids->current_node,
    ];

    // Load HTML items to populate the select options.
    $options = $this->getHtmlItemOptions();

    // Select element for HTML items.
    $form['box'] = [
      '#type' => 'select',
      '#title' => $this->t('Select HTML Item'),
      '#options' => $options,
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
   * Queries and returns options for the HTML item select field.
   *
   * @return array
   *   An associative array of options for the select field.
   */
  private function getHtmlItemOptions() {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'guide_html_item')
      ->sort('title', 'ASC')
      ->accessCheck(TRUE);
    $nids = $query->execute();

    $nodes = Node::loadMultiple($nids);
    $options = [];
    foreach ($nodes as $node) {
      $options[$node->id()] = $node->label();
    }

    return $options;
  }

  /**
   * Pre-fills the selected HTML item fields if one is selected.
   *
   * @param array $form
   *   The form definition array.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   */
  private function prefillSelectedHtmlItem(array &$form, FormStateInterface $form_state) {
    $selected = $form_state->getValue('box');

    if (!empty($selected)) {
      $selected_node = Node::load($selected);
      if ($selected_node) {
        $reference = $form_state->getValue('reference');
        $form['update_wrapper']['title'] = [
          '#type' => 'textfield',
          '#title' => $this->t('New Title:'),
          '#default_value' => $reference ? $this->t('This is just a reference and cannot be edited.') : $selected_node->label(),
          '#required' => !$reference,
          '#disabled' => $reference,
          '#states' => [
            'invisible' => [':input[name="reference"]' => ['checked' => TRUE]],
          ],
        ];
        $form['update_wrapper']['body'] = [
          '#type' => 'text_format',
          '#title' => $this->t('Body'),
          '#default_value' => $selected_node->get('field_text_box_item2')->value,
          '#disabled' => $reference,
          '#states' => [
            'invisible' => [':input[name="reference"]' => ['checked' => TRUE]],
            'required' => [':input[name="reference"]' => ['checked' => FALSE]],
          ],
        ];
      }
    }
  }

  public function validateFields(array &$form, FormStateInterface $form_state) {
    $reference = $form_state->getValue('reference');
    $title = $form_state->getValue('title');
    if (!$reference && empty($title)) {
      $form_state->setErrorByName('title', $this->t('Title: field is required.'));
    }
  }

  public function htmlItemSelectedAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['update_wrapper'];
  }

  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'HTML Item created successfully.', '#'.$this->getFormId());
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $current_box_id = $form_state->getValue('current_box');
    $current_box = Node::load($current_box_id);


    $html_id = $form_state->getValue('box');
    $html = Node::load($html_id);
    $item = $html->get('field_parent_item')->entity;

    if(!$form_state->getValue('reference')){
      $new_html = $html->createDuplicate();
      $new_item = $item->createDuplicate();

      $new_html->set('field_parent_item', $new_item);
      $new_html->set('title', $form_state->getValue('title'));
      $new_html->set('field_text_box_item2', [
        'value' => $form_state->getValue('body')['value'],
        'format' => $form_state->getValue('body')['format']
      ]);
      $new_html->save();

      $new_item->set('field_parent_box', $current_box);
      $new_item->set('title', $form_state->getValue('title'));
      $new_item->set('field_html_item', $new_html);

      $new_item->save();

      $item = $new_item;
    } else {
      $new_item = $item->createDuplicate();
      $new_item->set('field_lgms_database_link', TRUE);
      $new_item->save();
      $item = $new_item;
    }

    $boxList = $current_box->get('field_box_items')->getValue();
    $boxList[] = ['target_id' => $item->id()];

    $current_box->set('field_box_items', $boxList);
    $current_box->save();

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
