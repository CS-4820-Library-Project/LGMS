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
    $ajaxHelper = new FormHelper();

    // Get the selected guide
    $selected_guide_id = $form_state->getValue('guide_select');
    $original_guide = Node::load($selected_guide_id);

    if ($original_guide) {
      // Create a copy
      $cloned_guide = $original_guide->createDuplicate();
      $cloned_guide->set('title', $original_guide->label() . ' copy');
      $cloned_guide->setOwnerId(\Drupal::currentUser()->id());
      $cloned_guide->save();

      // 1. Clone Guide Boxes of Each Guide
      $ajaxHelper->clone_boxes($original_guide, $cloned_guide);

      // 2. Clone Pages & Subpages
      $ajaxHelper->clone_pages($original_guide, $cloned_guide);

      // Add a success message
      \Drupal::messenger()->addMessage($this->t('Guide created successfully.'));

      $form_state->setRedirectUrl($cloned_guide->toUrl('edit-form'));
    }
  }
}
