<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class EditGuideBoxForm extends FormBase {

  public function getFormId(): string
  {
    return 'edit_guide_box_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form_helper = new FormHelper();

    // Get the data from the URL
    $ids = (object) [
      'current_node' => \Drupal::request()->query->get('current_node'),
      'current_box' => \Drupal::request()->query->get('current_box'),
    ];

    // Set the prefix, suffix, and hidden fields
    $form_helper->set_form_data($form, $ids, $this->getFormId());

    // Load Nodes
    $current_node = Node::load($ids->current_node);
    $current_box = Node::load($ids->current_box);

    // Get Box parent
    $parent_page = $current_box->get('field_parent_node')->entity;

    // If the user can edit it from this page
    if($current_node->id() == $parent_page->id()){
      $form['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('New Box Title:'),
        '#default_value' => $current_box->label(),
      ];

      $form['published'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Draft mode:'),
        '#description' => $this->t('Un-check this box to publish.'),
        '#default_value' => !$current_box->isPublished(),
      ];

    } else {
      // Get the url for the parent node
      $node_url = $parent_page->toUrl()->toString();
      $link_html = '<a href="' . $node_url . '">' . $parent_page->label() . '</a>';

      $form['title'] = [
        '#markup' => 'This Box can not be edited from this Guide, you can edit it from: ' . $link_html,
      ];
      return $form;
    }


    $form['actions']['#type'] = 'actions';
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
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): \Drupal\Core\Ajax\AjaxResponse
  {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box Updated Successfully.', '#'.$this->getFormId());
  }

  /**
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    // Load the box
    $current_box = Node::load($form_state->getValue('current_box'));

    if ($current_box) {
      // Update Box
      $current_box->setTitle(rtrim($form_state->getValue('title')));
      $form_state->getValue('published')? $current_box->setUnpublished() : $current_box->setPublished();

      // Save updates
      $current_box->save();

      // Update last change date for parents.
      $ajaxHelper = new FormHelper();
      $ajaxHelper->updateParent($form, $form_state);
    }
  }
}
