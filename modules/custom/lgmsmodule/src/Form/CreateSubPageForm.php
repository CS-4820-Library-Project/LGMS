<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class CreateSubPageForm extends FormBase {

  public function getFormId() {
    return 'create_guide_sub_page_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $parent_page_id = \Drupal::request()->query->get('parent_page');

    // Load the parent guide page node.
    $parent_page_node = Node::load($parent_page_id);

    if ($parent_page_node) {
      // Add the parent guide page's name and ID to the form as markup elements.
      $form['parent_page_info'] = [
        '#type' => 'item',
        '#markup' => $this->t('You are adding a sub-page to the guide page: @name', [
          '@name' => $parent_page_node->label(),
        ]),
      ];

    } else {
      \Drupal::messenger()->addError($this->t('The specified parent guide page does not exist.'));
      return $form_state->setRedirect('<front>');
    }

    // The rest of your form elements remain the same.
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sub-page Title'),
      '#required' => TRUE,
    ];

    $form['field_description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#format' => 'full_html',
      '#required' => TRUE,
    ];


    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Sub-page'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $parent_page_id = \Drupal::request()->query->get('parent_page');

    if ($parent_page_node = Node::load($parent_page_id)) {
      $title = $form_state->getValue('title');
      $body_values = $form_state->getValue('field_description');

      $sub_page = Node::create([
        'type' => 'sub_page', // Assuming sub-pages are of the same type.
        'title' => $title,
        'field_description' => [
          'value' => $body_values['value'],
          'format' => $body_values['format'],
        ],
        'field_parent_page' => [
          'target_id' => $parent_page_id,
        ],
        'field_draft_mode' => $form_state->getValue('field_draft_mode'),
      ]);

      $sub_page->save();

      \Drupal::messenger()->addMessage($this->t('The sub page has been created with ID: @id', ['@id' => $sub_page->id()]));

      $form_state->setRedirect('entity.node.canonical', ['node' => $sub_page->id()]);
    } else {
      \Drupal::messenger()->addError($this->t('The parent guide page could not be loaded.'));
      $form_state->setRedirect('<front>');
    }
  }
}

