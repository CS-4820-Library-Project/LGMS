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
    $form['#prefix'] = '<div id="' . $this->getFormId() . '">';
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

    $current_node = Node::load($current_node);

    $current_box = Node::load($current_box);

    $parent_page = $current_box->get('field_parent_node')->getValue();
    $parent_page = Node::load($parent_page[0]['target_id']);


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
      $node_url = $parent_page->toUrl()->toString();
      $link_html = '<a href="' . $node_url . '">' . $parent_page->getTitle() . '</a>';

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

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box Updated Successfully.', '#'.$this->getFormId());
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $current_box = $form_state->getValue('current_box');
    $current_box = Node::load($current_box);

    $current_box->setTitle(rtrim($form_state->getValue('title')));
    $form_state->getValue('published') == '0'? $current_box->setPublished(): $current_box->setUnpublished();
    $current_box->save();

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
