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
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'guide')
      ->sort('title', 'ASC')
      ->accessCheck(TRUE);
    $result = $query->execute();
    $options = [];
    if (!empty($result)) {
      $nodes = Node::loadMultiple($result);
      foreach ($nodes as $node) {
        $options[$node->id()] = $node->getTitle();
      }
    }

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

  // Retrieves guide pages belonging to a guide
  private function getChildPages($guideId): array
  {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'guide_page')
      ->condition('field_parent_guide', $guideId)
      ->accessCheck(TRUE);
    $result = $query->execute();
    return Node::loadMultiple($result); // Assuming you have 'id()' on the page entity
  }

  // Retrieves guide boxes belonging to a guide page
  private function getChildBoxes($pageId): array
  {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'guide_box')
      ->condition('field_parent_node', $pageId)
      ->accessCheck(TRUE);
    $result = $query->execute();
    return Node::loadMultiple($result); // Assuming you have 'id()' on the box entity
  }

  /**
   * @throws EntityStorageException
   * @throws EntityMalformedException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $selected_guide_id = $form_state->getValue('guide_select');
    $original_guide = Node::load($selected_guide_id);
    if ($original_guide) {
      $cloned_guide = $original_guide->createDuplicate();
      $cloned_guide->save();

      // 1. Get Guide Pages of Original Guide
      $pages = $this->getChildPages($original_guide->id());

      // 2. Clone Guide Boxes of Each Guide
      $guide_boxes = $this->getChildBoxes($original_guide->id());
      $cloned_box_list = [];

      foreach ($guide_boxes as $box) {
        $cloned_box = $box->createDuplicate();
        $cloned_box->set('field_parent_node', $cloned_guide->id());
        $cloned_box->save();

        $cloned_box_list[] = ['target_id' => $cloned_box->id()];
      }

      // After cloning all boxes, update the cloned guide with the list of cloned boxes.
      if (!empty($cloned_box_list)) {
        $cloned_guide->set('field_child_boxes', $cloned_box_list);
        $cloned_guide->save();
      }

      // 3. Clone Pages & Subpages
      foreach ($pages as $page) {

        $cloned_page = $page->createDuplicate();
        $cloned_page->set('field_parent_guide', $cloned_guide->id());
        $cloned_page->save();

        $page_boxes = $this->getChildBoxes($page->id());
        $subpages = $this->getChildPages($page->id());

        $cloned_page_box_list = [];
        $cloned_subpage_list = [];

        // Page boxes
        foreach ($page_boxes as $box) {
          $cloned_page_box = $box->createDuplicate();
          $cloned_page_box->set('field_parent_node', $cloned_page->id());
          $cloned_page_box->save();

          $cloned_page_box_list[] = ['target_id' => $cloned_page_box->id()];
        }

        // Page subpages
        foreach ($subpages as $subpage){
          $cloned_subpage = $subpage->createDuplicate();
          $cloned_subpage->set('field_parent_guide', $cloned_page->id());
          $cloned_subpage->save();

          $cloned_subpage_list[] = ['target_id' => $cloned_subpage->id()];


          $subpage_boxes = $this->getChildBoxes($subpage->id());
          $cloned_subpage_box_list = [];

          foreach ($subpage_boxes as $box) {
            $cloned_subpage_box = $box->createDuplicate();
            $cloned_subpage_box->set('field_parent_node', $cloned_subpage->id());
            $cloned_subpage_box->save();

            $cloned_subpage_box_list[] = ['target_id' => $cloned_subpage_box->id()];
          }

          // After cloning all boxes, update the cloned subpages with the list of cloned boxes.
          if (!empty($cloned_subpage_box_list)) {
            $cloned_subpage->set('field_child_boxes', $cloned_subpage_box_list);
            $cloned_subpage->save();
          }
        }

        // After cloning all boxes, update the cloned pages with the list of cloned boxes.
        if (!empty($cloned_page_box_list)) {
          $cloned_page->set('field_child_boxes', $cloned_page_box_list);
          $cloned_page->save();
        }

        // After cloning all boxes, update the cloned pages with the list of cloned boxes.
        if (!empty($cloned_subpage_list)) {
          $cloned_page->set('field_child_pages', $cloned_subpage_list);
          $cloned_page->save();
        }

        $cloned_page_list[] = ['target_id' => $cloned_page->id()];
      }

      // After cloning all pages, update the cloned guide with the list of cloned pages.
      if (!empty($cloned_page_list)) {
        $cloned_guide->set('field_child_pages', $cloned_page_list);
        $cloned_guide->save();
      }


      // Add a success message
      \Drupal::messenger()->addMessage($this->t('Guide created successfully.'), MessengerInterface::TYPE_STATUS);

      $form_state->setRedirectUrl($cloned_guide->toUrl('edit-form'));
    }
  }

}
