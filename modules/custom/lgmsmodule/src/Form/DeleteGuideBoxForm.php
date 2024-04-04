<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class DeleteGuideBoxForm extends FormBase {

  public function getFormId() {
    return 'delete_guide_box_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $current_node = \Drupal::request()->query->get('current_node');

    $current_box = \Drupal::request()->query->get('current_box');

    $ids = (object) ['current_node' => $current_node, 'current_box' => $current_box];

    $form_helper = new FormHelper();

    $form_helper->set_prefix($form, $this->getFormId());

    $form_helper->set_form_fields_from_array($form,$ids);

    $current_node = Node::load($current_node);

    $current_box = Node::load($current_box);

    $parent_page = $current_box->get('field_parent_node')->getValue();
    $parent_page = Node::load($parent_page[0]['target_id']);

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

    $current_box = $form_state->getValue('current_box');
    $current_box = Node::load($current_box);

    $current_node = $form_state->getValue('current_node');
    $current_node = Node::load($current_node);

    $child_boxes = $current_node->get('field_child_boxes')->getValue();

    $child_boxes = array_filter($child_boxes, function ($box) use ($current_box) {
      return $box['target_id'] != $current_box->id();
    });

    $current_node->set('field_child_boxes', $child_boxes);
    $current_node->save();

    $parent_node = $current_box->get('field_parent_node')->getValue();
    $parent_node = Node::load($parent_node[0]['target_id']);

    if($current_node->id() == $parent_node->id()){
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'guide_page')
        ->condition('field_child_boxes', $current_box->id())
        ->accessCheck(TRUE);
      $result = $query->execute();

      foreach ($result as $page){
        $page = Node::load($page);
        $child_boxes = $page->get('field_child_boxes')->getValue();

        $child_boxes = array_filter($child_boxes, function ($box) use ($current_box) {
          return $box['target_id'] != $current_box->id();
        });

        $page->set('field_child_boxes', $child_boxes);
        $page->save();
      }

      $current_box?->delete();
    }

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
