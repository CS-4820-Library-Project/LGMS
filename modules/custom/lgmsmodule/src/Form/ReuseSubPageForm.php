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
    return 'lgmsmodule_reuse_subpage_form';
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

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $sub_page_id = $form_state->getValue('sub_page_select');
    $sub_page = Node::load($sub_page_id);

    if ($sub_page) {
      // Clone the sub-page
      $cloned_sub_page = $sub_page->createDuplicate();
      $cloned_sub_page->save();

      // Load child boxes related to the original sub-page
      $child_boxes = $this->getChildBoxes($sub_page_id);

      foreach ($child_boxes as $box) {
        // Clone each box
        $cloned_box = $box->createDuplicate();
        // Update the reference field on the cloned box to point to the cloned sub-page
        $cloned_box->set('field_parent_sub_page', $cloned_sub_page->id());
        $cloned_box->save();
      }

      drupal_set_message($this->t('Sub page @title has been successfully imported with its boxes.', ['@title' => $cloned_sub_page->label()]));

      // Redirect to the newly cloned sub-page.
      $form_state->setRedirect('entity.node.canonical', ['node' => $cloned_sub_page->id()]);
    } else {
      drupal_set_message($this->t('Failed to load the sub page.'), 'error');
    }
  }

  private function getSubPageOptions()
  {
    $options = [];
    $nodes = Node::loadMultiple();
    foreach ($nodes as $node) {
      if ($node->bundle() == 'sub_page') {
        $options[$node->id()] = $node->label();
      }
    }
    return $options;
  }
}
