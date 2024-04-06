<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ReuseGuideForm extends FormBase
{

  public function getFormId(): string
  {
    return 'lgmsmodule_reuse_guide_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    // Set the prefix, suffix, and hidden fields
    $form_helper = new FormHelper();
    $form_helper->set_prefix($form, $this->getFormId());

    $options = $form_helper->get_item_options('guide');

    $form['guide_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a Guide to Reuse'),
      '#options' => $options,
      '#empty_option' => count($options) <= 0? $this->t('The Website does not have any Guides to reuse') : $this->t('- Select a Guide -'),
      '#required' => TRUE,
      '#disabled' => count($options) <= 0,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reuse Guide'),
    ];

    return $form;
  }

  /**
   * @throws EntityStorageException
   * @throws EntityMalformedException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    // Get the selected guide
    $selected_guide_id = $form_state->getValue('guide_select');
    $original_guide = Node::load($selected_guide_id);

    if ($original_guide) {
      // Create a copy
      $cloned_guide = $original_guide->createDuplicate();
      $cloned_guide->set('title', $original_guide->label() . ' copy');
      $cloned_guide->save();

      // 1. Clone Guide Boxes of Each Guide
      $this->clone_boxes($original_guide, $cloned_guide);

      // 2. Clone Pages & Subpages
      $this->clone_pages($original_guide, $cloned_guide);

      // Add a success message
      \Drupal::messenger()->addMessage($this->t('Guide created successfully.'));

      $form_state->setRedirectUrl($cloned_guide->toUrl('edit-form'));
    }
  }

  private function clone_pages($parent, $new_parent){
    $pages = $parent->get('field_child_pages')->referencedEntities();

    $new_page_list = [];

    foreach ($pages as $page) {
      $cloned_page = $page->createDuplicate();
      $cloned_page->set('field_parent_guide', $new_parent->id());
      $cloned_page->save();

      $this->clone_boxes($page, $cloned_page);
      $this->clone_pages($page, $cloned_page);

      $new_page_list[] = ['target_id' => $cloned_page->id()];
    }

    // After cloning all boxes, update the cloned guide with the list of cloned boxes.
    if (!empty($new_page_list)) {
      $new_parent->set('field_child_pages', $new_page_list);
      $new_parent->save();
    }

  }

  private function clone_boxes($page, $new_page): void
  {
    $guide_boxes = $page->get('field_child_boxes')->referencedEntities();

    $new_box_list = [];

    foreach ($guide_boxes as $box) {
      $cloned_box = $box->createDuplicate();
      $cloned_box->set('field_parent_node', $new_page->id());
      $cloned_box->save();

      $new_box_list[] = ['target_id' => $cloned_box->id()];
    }

    // After cloning all boxes, update the cloned guide with the list of cloned boxes.
    if (!empty($new_box_list)) {
      $new_page->set('field_child_boxes', $new_box_list);
      $new_page->save();
    }
  }
}
