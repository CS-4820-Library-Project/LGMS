<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ReuseGuideForm extends FormBase
{

  public function getFormId()
  {
    return 'lgmsmodule_reuse_guide_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
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
      '#empty_option' => $this->t('- Select a Guide -'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reuse Guide'),
    ];

    return $form;
  }

  // Retrieves guide pages belonging to a guide
  private function getChildPages($guideId)
  {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'guide_page')
      ->condition('field_parent_guide', $guideId)
      ->accessCheck(TRUE);
    $result = $query->execute();
    return Node::loadMultiple($result); // Assuming you have 'id()' on the page entity
  }

  // Retrieves guide boxes belonging to a guide page
  private function getChildBoxes($pageId)
  {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'guide_box')
      ->condition('field_parent_node', $pageId)
      ->accessCheck(TRUE);
    $result = $query->execute();
    return Node::loadMultiple($result); // Assuming you have 'id()' on the box entity
  }
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $selected_guide_id = $form_state->getValue('guide_select');
    $original_guide = Node::load($selected_guide_id);
    if ($original_guide) {
      $cloned_guide = $original_guide->createDuplicate();
      $cloned_guide->save();

      // 1. Get Guide Pages of Original Guide
      $pages = $this->getChildPages($original_guide->id());

      // 2. Clone Pages
      foreach ($pages as $page) {
        $cloned_page = $page->createDuplicate();
        $cloned_page->set('field_parent_guide', $cloned_guide->id()); // Update Parent Guide reference
        $cloned_page->save();

        // 3. Clone Guide Boxes of Each Page
        $boxes = $this->getChildBoxes($page->id());
        foreach ($boxes as $box) {
          $cloned_box = $box->createDuplicate();
          $cloned_box->set('field_parent_node', $cloned_page->id()); // Update Parent Page reference
          $cloned_box->save();
        }
      }

      $form_state->setRedirectUrl($cloned_guide->toUrl('edit-form'));
    }
  }
}
