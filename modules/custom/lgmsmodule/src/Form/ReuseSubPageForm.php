<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class ReuseSubPageForm extends FormBase
{

  public function getFormId()
  {
    // TODO: Implement getFormId() method.
    return 'reuse_subpage_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['sub_page_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a Sub Page to Import'),
      '#options' => $this->getSubPageOptions(),
      '#empty_option' => $this->t('- Select a Sub Page -'),
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import Sub Page'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $sub_page_id = $form_state->getValue('sub_page_select');
    $current_page_id = \Drupal::request()->query->get('parent_page'); // Adjust based on how you're passing the current page ID

    $sub_page = Node::load($sub_page_id);
    $current_page = Node::load($current_page_id);

    if ($sub_page && $current_page) {
      // Clone the sub-page
      $cloned_sub_page = $sub_page->createDuplicate();

      // Here's the crucial change: Set the parent page of the cloned sub-page to the current page
      $cloned_sub_page->set('field_parent_page', $current_page_id); // Adjust 'field_parent_page' to your actual field name
      $cloned_sub_page->save();

      // Load child boxes related to the original sub-page
      $child_boxes = $this->getChildBoxes($sub_page_id);

      foreach ($child_boxes as $box) {
        // Clone each box
        $cloned_box = $box->createDuplicate();
        // Update the reference field on the cloned box to point to the cloned sub-page
        $cloned_box->set('field_parent_sub_page', $cloned_sub_page->id()); // Ensure this is your actual field name
        $cloned_box->save();
      }

      \Drupal::messenger()->addMessage($this->t('Sub page @title has been successfully imported with its boxes.', ['@title' => $cloned_sub_page->label()]));

      // Redirect to the newly cloned sub-page.
      $form_state->setRedirect('entity.node.canonical', ['node' => $cloned_sub_page->id()]);
    } else {
      \Drupal::messenger()->addMessage($this->t('Failed to load the sub page or current page ID is missing.'), 'error');
    }
  }



  private function getSubPageOptions() {
    $options = [];
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'sub_page')
      ->accessCheck(TRUE); // Ensure access controls are respected in this query
    $result = $query->execute();
    if (!empty($result)) {
      $nodes = Node::loadMultiple($result);
      foreach ($nodes as $node) {
        $options[$node->id()] = $node->label();
      }
    }
    return $options;
  }


  private function getChildBoxes($pageId) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'box') // Assuming 'box' is the machine name for your box content type
      ->condition('field_parent_page', $pageId) // Adjust field name as necessary
      ->accessCheck(TRUE); // Explicitly setting access check
    $result = $query->execute();
    return Node::loadMultiple($result);
  }

}
