<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Provides a form for creating new guide page entities.
 *
 * This form allows users to create a new page within a guide, specifying details
 * such as the page title, position in the guide's structure, and whether the page
 * should be published or saved in draft mode. Users can also choose to hide the
 * page's description.
 */
class CreateGuidePageForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'create_guide_page_form';
  }

  /**
   * Builds the guide page creation form.
   *
   * @param array $form An associative array containing the structure of the form.
   * @param FormStateInterface $form_state The current state of the form.
   * @param mixed $ids (optional) Identifiers necessary for form construction.
   *
   * @return array The form structure.
   */

  public function buildForm(array $form, FormStateInterface $form_state, $ids = null): array
  {
    // Set the prefix, suffix, and hidden fields
    $form_helper = new FormHelper();
    $form_helper->set_form_data($form,$ids, $this->getFormId());

    // Title field
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page Title'),
      '#required' => TRUE,
    ];

    $form['hide_description'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide description'),
    ];

    // Description field
    $form['field_description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#format' => 'full_html', // Set the default format or use user preferred format
      '#states' => [
        'invisible' => [
          ':input[name="hide_description"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="hide_description"]' => ['checked' => False],
        ],
      ],
    ];

    $form['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#options' => $form_helper->get_position_options($form_state, property_exists($ids, 'current_guide') ? $ids->current_guide : null),
      '#required' => TRUE,
    ];

    $form['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Draft Mode'),
      '#description' => $this->t('Check this box if the page is still in draft.'),
    ];

    $form['#validate'][] = '::validateFields';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Page'),
      '#button_type' => 'primary',
      '#ajax' =>[
        'callback' => '::submitAjax',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * Validates form fields before submission.
   *
   * Ensures required fields are filled and validates specific conditions, such
   * as the necessity of a description if it's not hidden.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   */
  public function validateFields(array &$form, FormStateInterface $form_state): void
  {
    $hide = $form_state->getValue('hide_description');
    $desc = $form_state->getValue('field_description')['value'];
    if (!$hide && empty($desc)) {
      $form_state->setErrorByName('field_description', $this->t('Description: field is required.'));
    }
  }

  /**
   * Handles AJAX form submissions.
   *
   * Provides a smoother user experience by submitting the form and providing feedback
   * via AJAX, without requiring a full page reload.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return AjaxResponse An AJAX response that updates the client-side state.
   * @throws EntityMalformedException
   */

  public function submitAjax(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Page created successfully.', '#'.$this->getFormId());
  }

  /**
   * Processes the submission of the guide page creation form.
   *
   * Takes the input from the form, validates it, and uses it to create a new
   * guide page node entity, setting its properties according to the form values.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   *
   * @throws EntityStorageException If there is an issue saving the guide page entity.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $ajaxHelper = new FormHelper();

    // Get and Load the current guide node.
    $current_guide = $form_state->getValue('current_guide');
    $current_guide = Node::load($current_guide);

    if($current_guide){
      // Get the position
      $parent = $form_state->getValue('position');
      $parent = Node::load($parent);

      // Create the new page
      $new_page = Node::create([
        'type' => 'guide_page',
        'title' => $form_state->getValue('title'),
        'field_description' => $form_state->getValue('field_description'),
        'field_parent_guide' => $parent,
        'field_hide_description' => $form_state->getValue('hide_description') == '1',
        'status' => $form_state->getValue('published') == '0',
      ]);
      $new_page->save();

      // Set the new node value for redirection
      $form_state->setValue('current_node', $new_page->id());

      //Update parents
      $ajaxHelper->add_child($parent, $new_page, 'field_child_pages');
      $ajaxHelper->updateParent($form, $form_state);
    }

  }
}
