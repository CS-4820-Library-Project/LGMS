<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class DeleteGuideForm extends FormBase {

  public function getFormId(): string
  {
    return 'delete_guide_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form_helper = new FormHelper();

    // Get the data from the URL
    $ids = (object) [
      'guide_id' => \Drupal::request()->query->get('guide_id'),
    ];

    // Set the prefix, suffix, and hidden fields
    $form_helper->set_form_data($form, $ids, $this->getFormId());

    // Warning message
    $form['warning'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Are you sure you want to delete this guide?</strong>
                                    This will remove it and any pages and boxes it directly owns
                                    (but not links to pages and boxes owned by other pages or guides, nor any content items).'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#required' => True,
    ];

    // Actions wrapper
    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Delete button
    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#button_type' => 'danger',
    ];


    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $helper = new FormHelper();
    // Get the id of the guide to be deleted
    $guide = Node::load($form_state->getValue('guide_id'));

    // Delete guide and it's pages
    $helper->deletePages($guide, True);

    // Redirect the user to the dashboard
    $form_state->setRedirect('lgmsmodule.dashboard_overview');
  }
}
