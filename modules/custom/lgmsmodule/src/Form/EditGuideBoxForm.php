<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class EditGuideBoxForm extends FormBase {

  public function getFormId() {
    return 'edit_guide_box_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    $current_node = \Drupal::request()->query->get('current_node');
    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $current_node,
    ];

    $current_box = \Drupal::request()->query->get('current_box');
    $form['current_box'] = [
      '#type' => 'hidden',
      '#value' => $current_box,
    ];

    $page = Node::load($current_node);

    if ($page->bundle() === 'guide'){
      // Get the list of guide pages
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'guide_page')
        ->condition('field_parent_guide', $page->id())
        ->accessCheck(TRUE);
      $result = $query->execute();

      // Get the first page
      $first_node_id = reset($result);
      $page = Node::load($first_node_id);
    }

    $current_box = Node::load($current_box);

    $parent_page = $current_box->get('field_parent_page')->getValue();
    $parent_page = Node::load($parent_page[0]['target_id']);


    if($page->id() == $parent_page->id()){
      $form['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('New Box Title:'),
        '#required' => TRUE,
      ];
    } else {
      $form['title'] = [
        '#markup' => 'This Box can not be edited from this Guide',
      ];
      return $form;
    }


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

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box Updated Successfully.');
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $current_box = $form_state->getValue('current_box');
    $current_box = Node::load($current_box);

    $current_box->setTitle(rtrim($form_state->getValue('title')));
    $current_box->save();

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
