<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Form for creating new guide box entities.
 *
 * Provides a simple form within the lgmsmodule for creating box entities that
 * can be associated with a guide. These box entities can hold various types of
 * content and are intended to structure guide content into manageable sections.
 */
class CreateGuideBoxForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'create_guide_box_form';
  }

  /**
   * Builds the create guide box form.
   *
   * @param array $form An associative array containing the initial structure of the form.
   * @param FormStateInterface $form_state The current state of the form.
   * @param mixed $ids Optional identifiers for form construction, typically including
   *                   the parent node ID and other contextual data.
   *
   * @return array The modified form structure including fields for the box title
   *               and publication status.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ids = null): array
  {
    // Set the prefix, suffix, and hidden fields
    $form_helper = new FormHelper();
    $form_helper->set_form_data($form,$ids, $this->getFormId());

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Box Title:'),
      '#required' => TRUE,
    ];

    $form['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Draft mode:'),
      '#description' => $this->t('Un-check this box to publish.'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#ajax' =>[
        'callback' => '::submitAjax',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * AJAX callback for the form submission.
   *
   * Handles the form submission using AJAX to provide a smoother user experience.
   * On success, it provides feedback and may update the user interface to reflect
   * the newly created box entity.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return AjaxResponse An AJAX response object to handle client-side updates.
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box created successfully.', '#'.$this->getFormId());
  }

  /**
   * Processes the guide box creation form submission.
   *
   * Takes the input from the form, validates it, and uses it to create a new
   * guide box node entity. It associates this box with its parent guide and
   * updates related entities as necessary.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   *
   * @throws EntityStorageException If there is an issue saving the box entity.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    // Get the current page
    $curr_page = $form_state->getValue('current_node');
    $curr_page = Node::load($curr_page);

    // Create the new Box
    $new_box = Node::create([
      'type' => 'guide_box',
      'title' => $form_state->getValue('title'),
      'field_parent_node' => $curr_page,
      'status' => $form_state->getValue('published') == '0',
    ]);
    $new_box->save();

    // Add the box to the page's child boxes
    $box_list = $curr_page->get('field_child_boxes')->getValue();
    $box_list[] = ['target_id' => $new_box->id()];

    $curr_page->set('field_child_boxes', $box_list);
    $curr_page->save();

    // Update parents
    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
