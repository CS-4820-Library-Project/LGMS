<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GuideImportForm extends FormBase
{

  public function getFormId()
  {
    return 'lgmsmodule_guide_import_form';
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
      '#title' => $this->t('Select a Guide to Import'),
      '#options' => $options,
      '#empty_option' => $this->t('- Select a Guide -'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import Guide'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $selected_guide_id = $form_state->getValue('guide_select');
    $original_guide = Node::load($selected_guide_id);
    if ($original_guide) {
      $cloned_guide = $original_guide->createDuplicate();
      $cloned_guide->save();
      $form_state->setRedirectUrl($cloned_guide->toUrl('edit-form'));
    }
  }
}
