<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a form for editing a guide.
 *
 * This form allows users to edit the title, description, subjects, type, group,
 * and publication status of a guide. It dynamically adjusts available options
 * based on the guide's current settings and taxonomy term availability.
 */
class EditGuideForm extends FormBase {

  /**
   * Checks if the user can edit their own article.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Drupal\Core\Session\AccountInterface $account
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(Request $request, AccountInterface $account) {
    $nid = $request->query->get('guide_id');
    $node = Node::load($nid);

    if ($node && $node->getType() == 'guide' && $node->access('update')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'edit_guide_form';
  }

  /**
   * Builds the guide edit form.
   *
   * @param array $form An associative array containing the structure of the form.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return array The form structure, including fields for editing guide properties.
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    // Set the prefix, suffix, and hidden fields
    $form_helper = new FormHelper();

    // Get the data from the URL
    $ids = (object) [
      'guide_id' => \Drupal::request()->query->get('guide_id'),
    ];

    // Set the prefix, suffix, and hidden fields
    $form_helper->set_form_data($form, $ids, $this->getFormId());

    $guide = Node::load($ids->guide_id);

    if ($guide->bundle() != 'guide'){
      throw new AccessDeniedHttpException();
    }


    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page Title'),
      '#default_value' => $guide->label(),
      '#required' => True,
    ];

    $form['hide_description'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide description'),
      '#default_value' => $guide->get('field_hide_description')->value,
      '#ajax' => [
        'callback' => '::hideDescriptionCallback', // Update the form to trigger resizing
        'wrapper' => 'update-wrapper',
        'event' => 'change',
      ],
    ];

    // Description field with state controlled by hide_description checkbox.
    $form['description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#states' => [
        'invisible' => [
          ':input[name="hide_description"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="hide_description"]' => ['checked' => False],
        ],
      ],
      '#default_value' => $guide->get('field_description')->value,
      '#format' => $guide->get('field_description')->format,
    ];

    // Get the current terms the guide has
    $current_terms = [];
    if ($guide->hasField('field_lgms_guide_subject')) {
      foreach ($guide->get('field_lgms_guide_subject')->getValue() as $item) {
        $current_terms[] = $item['target_id'];
      }
    }

    // Load all terms from the LGMS_Guide_Subject taxonomy
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('LGMS_Guide_Subject');

    // Add the terms the guide has
    $form['subjects'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Guide Subjects'),
      '#options' => [],
      '#default_value' => $current_terms, // Pre-select the checkboxes for current terms
      '#required' => True,
    ];

    // Populate the options that the guide does not currently have for checkboxes
    foreach ($terms as $term) {
      $form['subjects']['#options'][$term->tid] = $term->name;
    }

    // Load LGMS_Guide_Type taxonomy terms
    $guide_type_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('LGMS_Guide_Type');
    $guide_type_options = [];
    foreach ($guide_type_terms as $term) {
      $guide_type_options[$term->tid] = $term->name;
    }

    // Add LGMS_Guide_Type select field
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Guide Type'),
      '#options' => $guide_type_options,
      '#default_value' => $guide->get('field_lgms_guide_type')->target_id,
      '#required' => TRUE,
    ];

    // Load LGMS_Guide_Group taxonomy terms
    $guide_group_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('LGMS_Guide_Group');
    $guide_group_options = [];
    foreach ($guide_group_terms as $term) {
      $guide_group_options[$term->tid] = $term->name;
    }

    // Add LGMS_Guide_Group select field
    $form['group'] = [
      '#type' => 'select',
      '#title' => $this->t('Guide Group'),
      '#options' => $guide_group_options,
      '#default_value' => $guide->get('field_lgms_guide_group')->target_id,
      '#required' => TRUE,
    ];

    $form['draft_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Draft Mode'),
      '#description' => $this->t('Check this box if the Guide is still in draft mode.'),
      '#default_value' => !$guide->isPublished(),
    ];

    $form['#validate'][] = '::validateFields';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Edit'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::submitAjax',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * Validates the form input.
   *
   * Ensures the description is provided if the 'Hide description' checkbox is not checked.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   */
  public function validateFields(array &$form, FormStateInterface $form_state): void
  {
    $hide = $form_state->getValue('hide_description');
    $desc = $form_state->getValue('description')['value'];

    if (!$hide && empty($desc)) {
      $form_state->setErrorByName('field_description', $this->t('Description field is required.'));
    }
  }

  /**
   * AJAX callback for toggling the description field visibility.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   *
   * @return AjaxResponse The AJAX response to handle form resizing.
   */
  public function hideDescriptionCallback(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    return new AjaxResponse();
  }

  /**
   * Handles AJAX form submission.
   *
   * Processes the form submission via AJAX, providing a smoother user experience
   * by offering immediate feedback without requiring a page refresh.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return AjaxResponse An AJAX response for the form submission.
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    // Create an array of AJAX commands.
    $ajaxHelper = new FormHelper();

    //For redirection
    $form_state->setValue('current_node', $form_state->getValue('guide_id'));

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Guide updated successfully.', '#'.$this->getFormId());
  }

  /**
   * Processes the submission of the guide edit form.
   *
   * Updates the guide node with the new values from the form. Handles the
   * complexity of multiple and single value fields such as taxonomy terms and
   * publication status.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    // Retrieve the guide_id passed to the form.
    $guide_id = $form_state->getValue('guide_id');

    // Load the guide node.
    $guide = Node::load($guide_id);
    if (!$guide) {
      \Drupal::messenger()->addError($this->t('Failed to load the guide.'));
      return;
    }

    // Update the guide fields with the form values.
    $guide->setTitle($form_state->getValue('title'));
    $guide->set('field_hide_description', $form_state->getValue('hide_description'));
    $guide->set('field_description', $form_state->getValue(['description']));

    // Subjects (handling multiple values).
    $subjects_values = array_filter($form_state->getValue('subjects'));
    $guide->set('field_lgms_guide_subject', array_values($subjects_values));

    // Guide Type (single value).
    $guide->set('field_lgms_guide_type', $form_state->getValue('type'));

    // Guide Group (single value).
    $guide->set('field_lgms_guide_group', $form_state->getValue('group'));

    // Handle draft mode as published status.
    $form_state->getValue('draft_mode')? $guide->setUnpublished() : $guide->setPublished();

    try {
      $guide->save();
    } catch (EntityStorageException $e) {
      \Drupal::messenger()->addError($this->t('An error occurred while saving the guide.'));
      \Drupal::logger('lgmsmodule')->error($e->getMessage());
    }
  }
}
