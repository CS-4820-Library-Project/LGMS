<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class FormHelper {

  /**
   * @throws EntityMalformedException
   */
  public function submitModalAjax(array &$form, FormStateInterface $form_state, String $message): AjaxResponse
  {
    // Create an array of AJAX commands.
    $response = new AjaxResponse();

    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal-form', $form));
      return $response;
    }

    // Close the modal dialog.
    $response->addCommand(new CloseModalDialogCommand());

    $curr_node = $form_state->getValue('current_node');
    $curr_node = Node::load($curr_node);

    $curr_node_url = $curr_node->toUrl()->toString();
    $curr_node_url = str_ireplace('LGMS/', '', $curr_node_url);

    $response->addCommand(new RedirectCommand(Url::fromUri('internal:' . $curr_node_url)->toString()));

    \Drupal::messenger()->addMessage($message);
    return $response;
  }

  public function updateParent(array &$form, FormStateInterface $form_state,)
  {
    $current_node = $form_state->getValue('current_node');
    $current_node = Node::load($current_node);

    if($current_node->getType() === 'guide'){
      $current_node->set('changed', \Drupal::time()->getRequestTime());
      $current_node->save();
    } else if ($current_node->getType() === 'guide_page'){
      $current_node->set('changed', \Drupal::time()->getRequestTime());
      $current_node->save();

      $guide = $current_node->get('field_parent_guide')->getValue();
      $guide = Node::load($guide[0]['target_id']);

      $guide->set('changed', \Drupal::time()->getRequestTime());
      $guide->save();
    }

    $current_node->set('changed', \Drupal::time()->getRequestTime());
    $current_node->save();
  }
}
