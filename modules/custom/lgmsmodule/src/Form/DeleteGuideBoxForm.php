<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DeleteGuideBoxForm extends FormBase {

  public function getFormId() {
    return 'delete_guide_box_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_helper = new FormHelper();

    // Get the data from the URL
    $ids = (object) [
      'current_node' => \Drupal::request()->query->get('current_node'),
      'current_box' => \Drupal::request()->query->get('current_box')
    ];

    // if Either is missing, deny access to the form
    if (empty($ids->current_node) || empty($ids->current_box)) {
      throw new AccessDeniedHttpException();
    }

    // Set the prefix, suffix, and hidden fields
    $form_helper->set_form_data($form, $ids, $this->getFormId());

    // Load the node and box
    $current_node = Node::load($ids->current_node);
    $current_box = Node::load($ids->current_box);

    $parent_page = $current_box->get('field_parent_node')->entity;

    // Decide the message based on if the box is being deleted from its parent page or not
    if($current_node->id() == $parent_page->id()){
      $title = $this->t('<Strong>Are you Sure you want to Delete This Box?</Strong>
                                if you delete this box, it will be permanently Deleted and restoring it would be impossible!!');
    } else {
      $title = $this->t('This box will be deleted only from this page');
    }

    $form['Delete'] = [
      '#type' => 'checkbox',
      '#title' => $title,
      '#required' => True
    ];


    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
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

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box was deleted Successfully.', '#'.$this->getFormId());
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    // Load the current box and node
    $current_box = Node::load($form_state->getValue('current_box'));
    $current_node = Node::load($form_state->getValue('current_node'));

    // Remove box from the current page child boxes
    $ajaxHelper->remove_child_box($current_node, $current_box);

    // Box's parent node
    $parent_node = $current_box->get('field_parent_node')->entity;

    // if the box is being deleted from its parent, then delete all boxes that reference it
    if($current_node->id() == $parent_node->id()){
      $ajaxHelper->delete_box($current_box);
    }

    // Update parent
    $ajaxHelper->updateParent($form, $form_state);
  }
}
