<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\lgmsmodule\sql\sqlMethods;
use Drupal\node\Entity\Node;

class DeleteGuidePageForm extends FormBase {

  public function getFormId() {
    return 'delete_guide_page_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_page = \Drupal::request()->query->get('current_page');

   // $current_node = $form_state->getValue('current_page');
   // $current_node = Node::load($current_page);

    $page_title = $current_page;


    $form['current_page'] = [
      '#type' => 'hidden',
      '#value' => $current_page,
    ];
    // Display the current page title or 'null'.
    $form['page_info'] = [
      '#type' => 'item',
      '#markup' => $this->t('Current Page: @title', ['@title' => $page_title]),
    ];

    $form['Delete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Are you sure you want to delete this page?</strong>
                              Deleting this page will permanently remove it and cannot be undone.'),
      '#required' => True,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete Page'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $sqlMethods = new sqlMethods(\Drupal::database());

    $current_page_id = $form_state->getValue('current_page');
    $guide_id=  $sqlMethods->getGuideNodeIdByPageId($current_page_id);
    //$guide_id = \Drupal::service('lgmsmodule.guide_service')->getGuideNodeIdByPageId($current_page_id);
    $current_page = Node::load($current_page_id);

    if ($current_page && $current_page->bundle() === 'guide_page') {
      // Perform the delete operation
      $current_page->delete();

      \Drupal::messenger()->addMessage($this->t('The guide page has been deleted.'));
      $form_state->setRedirect('entity.node.canonical', ['node' => $guide_id]);
    } else {
      // If the guide page does not exist or is not of the correct type, display an error message.
      \Drupal::messenger()->addError($this->t('The guide page could not be found or is not of the correct type.'));
    }
  }
}
