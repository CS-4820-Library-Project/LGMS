<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Provides a form to confirm and delete a guide.
 *
 * This form is used to delete a guide and its directly owned pages and boxes
 * from the system. It provides a confirmation step to prevent accidental deletions.
 * It does not delete links to pages and boxes owned by other pages or guides,
 * nor does it delete content items associated with those pages or boxes.
 */
class DeleteGuideForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'delete_guide_form';
  }


  /**
   * Builds the guide deletion confirmation form.
   *
   * @param array $form An associative array containing the structure of the form.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return array The form structure, including a warning message and a deletion confirmation checkbox.
   */
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
      '#title' => $this->t('<strong>Are you sure you want to delete this guide @page_title?</strong>
                                    This will remove it and any pages and boxes it directly owns
                                    (but not links to pages and boxes owned by other pages or guides,
                                     nor any content items).', ['@page_title' => Node::load($ids->guide_id)->label()]),
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

  /**
   * Handles the submission of the guide deletion form.
   *
   * Executes the deletion of the specified guide, including any pages and boxes
   * it directly owns, based on user confirmation. It ensures the guide is properly
   * removed from the system, then redirects the user to a safe location, typically
   * the dashboard overview page.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   */

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
