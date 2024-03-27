<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class DeleteGuideForm extends FormBase {

  public function getFormId() {
    return 'delete_guide_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $node_id = NULL) {
    $node_id = \Drupal::request()->query->get('node_id');
    \Drupal::logger('lgmsmodule')->notice('Node ID: @id, Type: @type', ['@id' => $node_id, '@type' => gettype($node_id)]);

    $current_guide = NULL;
    if ($node_id) {
      $current_guide = Node::load($node_id);
    }

    if ($current_guide) {
      $form['#prefix'] = '<div id="modal-form">';
      $form['#suffix'] = '</div>';

      // Warning message
      $form['warning'] = [
        '#type' => 'markup',
        '#markup' => $this->t('<strong>Are you sure you want to delete this guide?</strong> Deleting this guide will remove it permanently from the system.'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];

      // Hidden field to store the current guide ID
      $form['current_node'] = [
        '#type' => 'hidden',
        '#value' => $current_guide->id(),
      ];

      // Actions wrapper
      $form['actions'] = [
        '#type' => 'actions',
      ];

      // Cancel button
      $form['actions']['cancel'] = [
        '#type' => 'button',
        '#value' => $this->t('Cancel'),
        '#attributes' => ['class' => ['use-ajax']],
        '#ajax' => [
          'callback' => '::closeModalForm',
        ],
      ];

      // Delete button
      $form['actions']['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#button_type' => 'danger',
      ];

      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    } else {
      // Error message if the guide is not found
      $form['error'] = [
        '#type' => 'markup',
        '#markup' => $this->t('The guide could not be found.'),
      ];
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node_id = $form_state->getValue('current_node');
    if ($node = Node::load($node_id)) {
      $node->delete();
      \Drupal::messenger()->addMessage($this->t('Guide has been deleted.'));
    }
    \Drupal::logger('lgmsmodule')->notice('Node ID: @id, Type: @type');
    $form_state->setRedirect('lgmsmodule.dashboard_overview');
  }

  public function closeModalForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new \Drupal\Core\Ajax\CloseModalDialogCommand());
    return $response;
  }
}
