<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;

/**
 * Form handler for editing a guide box.
 *
 * Enables users to change the title and publication status of an existing guide box. It checks if
 * the box is being edited within its parent guide context, and if not, it provides a link to the
 * appropriate parent guide for editing.
 */
class EditGuideBoxForm extends FormBase {

  /**
   * Checks if the user can edit their own article.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Drupal\Core\Session\AccountInterface $account
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(Request $request, AccountInterface $account) {
    $nid = $request->query->get('current_box');
    $node = Node::load($nid);

    if ($node && $node->getType() == 'guide_box' && $node->access('update')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'edit_guide_box_form';
  }

  /**
   * Builds the guide box edit form.
   *
   * @param array $form An associative array containing the structure of the form.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return array The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form_helper = new FormHelper();

    // Get the data from the URL
    $ids = (object) [
      'current_node' => \Drupal::request()->query->get('current_node'),
      'current_box' => \Drupal::request()->query->get('current_box'),
    ];

    // Set the prefix, suffix, and hidden fields
    $form_helper->set_form_data($form, $ids, $this->getFormId());

    // Load Nodes
    $current_node = Node::load($ids->current_node);
    $current_box = Node::load($ids->current_box);

    // Get Box parent
    $parent_page = $current_box->get('field_parent_node')->entity;

    // If the user can edit it from this page
    if($current_node->id() == $parent_page->id()){
      $form['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('New Box Title:'),
        '#default_value' => $current_box->label(),
      ];

      $form['published'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Draft mode:'),
        '#description' => !$current_node->isPublished() ? $this->t('Please publish the page First') : $this->t('Un-check this box to publish.'),
        '#default_value' => $current_box->isPublished() == '0',
        '#disabled' => !$current_node->isPublished(),
      ];

    } else {
      // Get the url for the parent node
      $node_url = $parent_page->toUrl()->toString();
      $link_html = '<a href="' . $node_url . '">' . $parent_page->label() . '</a>';

      $form['title'] = [
        '#markup' => 'This Box can not be edited from this Guide, you can edit it from: ' . $link_html,
      ];
      return $form;
    }


    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::submitAjax',
        'event' => 'click',
      ],
    ];

    return $form;
  }


  /**
   * AJAX submission handler for the edit guide box form.
   *
   * Processes the form submission using AJAX to provide a smoother user experience
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
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box Updated Successfully.', '#'.$this->getFormId());
  }

  /**
   * Processes the submission of the guide box edit form.
   *
   * Updates the title and publication status of the guide box based on user input.
   * It checks if the current guide box can be edited in the current context and
   * performs the update accordingly.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    // Load the box
    $current_box = Node::load($form_state->getValue('current_box'));

    if ($current_box) {
      // Update Box
      $current_box->setTitle(rtrim($form_state->getValue('title')));
      $form_state->getValue('published')? $current_box->setUnpublished() : $current_box->setPublished();

      // Save updates
      $current_box->save();

      // Update last change date for parents.
      $ajaxHelper = new FormHelper();
      $ajaxHelper->updateParent($form, $form_state);
    }
  }
}
