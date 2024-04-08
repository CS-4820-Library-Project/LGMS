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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


/**
 * Provides a form for deleting guide boxes.
 *
 * This form allows administrators to delete guide boxes from the system. It
 * includes a confirmation step to ensure that boxes are not deleted
 * unintentionally. Depending on the context, the form may delete the box
 * entirely or just remove its association with a specific guide.
 */
class DeleteGuideBoxForm extends FormBase {

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

    if ($node && $node->getType() == 'guide_box' && $node->access('delete')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'delete_guide_box_form';
  }


  /**
   * Builds the deletion confirmation form.
   *
   * @param array $form An associative array containing the structure of the form.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return array The form structure, including the confirmation checkbox and deletion action.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form_helper = new FormHelper();

    // Get the data from the URL
    $ids = (object) [
      'current_node' => \Drupal::request()->query->get('current_node'),
      'current_box' => \Drupal::request()->query->get('current_box')
    ];

    // Set the prefix, suffix, and hidden fields
    $form_helper->set_form_data($form, $ids, $this->getFormId());

    // Load the node and box
    $current_node = Node::load($ids->current_node);
    $current_box = Node::load($ids->current_box);

    $parent_page = $current_box->get('field_parent_node')->entity;

    // Decide the message based on if the box is being deleted from its parent page or not
    if($current_node->id() == $parent_page->id()){
      $title = $this->t('<Strong>Are you Sure you want to Delete This Box?</Strong>
                                if you delete this box, it will be permanently Deleted and restoring it would be impossible!!');
    } else {
      $title = $this->t('This box will be deleted only from this page');
    }

    $form['Delete'] = [
      '#type' => 'checkbox',
      '#title' => $title,
      '#required' => True
    ];


    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#button_type' => 'primary',
    ];

    $form['actions']['submit']['#ajax'] = [
      'callback' => '::submitAjax',
      'event' => 'click',
    ];

    return $form;
  }

  /**
   * Handles AJAX submissions for the deletion form.
   *
   * Provides a smoother user experience by processing form submissions via AJAX,
   * offering immediate feedback and avoiding full page reloads.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The current state of the form.
   *
   * @return AjaxResponse An AJAX response to update the client-side state.
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box was deleted Successfully.', '#'.$this->getFormId());
  }

  /**
   * Processes the form submission for guide box deletion.
   *
   * Executes the deletion based on the user confirmation. If the box is being
   * deleted from its parent, it ensures that all references and child content
   * are also appropriately handled.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $ajaxHelper = new FormHelper();

    // Load the current box and node
    $current_box = Node::load($form_state->getValue('current_box'));
    $current_node = Node::load($form_state->getValue('current_node'));

    // Remove box from the current page child boxes
    $ajaxHelper->remove_child($current_node, $current_box, 'field_child_boxes');

    // Box's parent node
    $parent_node = $current_box->get('field_parent_node')->entity;

    // if the box is being deleted from its parent, then delete all boxes that reference it
    if($current_node->id() == $parent_node->id()){
      $ajaxHelper->delete_box($current_box);
    }

    // Update parent
    $ajaxHelper->updateParent($form, $form_state);
  }
}
