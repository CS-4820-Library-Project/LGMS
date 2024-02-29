<?php
namespace Drupal\lgmsmodule\Form;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class GuidePageImportForm extends FormBase {

  public function getFormId() {
    return 'lgmsmodule_guide_page_import_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_guide = \Drupal::request()->query->get('current_guide');

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
    ];

    $form['guide_page_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a Guide Page to Import'),
      '#options' => $this->getGuidePageOptions(),
      '#empty_option' => $this->t('- Select a Guide Page -'),
      '#required' => TRUE,
    ];

    $form['current_guide'] = [
      '#type' => 'hidden',
      '#value' => $current_guide,
    ];

    $form['cloned_guide_page'] = [
      '#type' => 'hidden',
      '#value' => NULL, // Initialize with NULL value
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import Guide Page'),
      '#button_type' => 'primary',
    ];

    $form['actions']['submit']['#ajax'] = [
      'callback' => '::submitAjax',
      'event' => 'click',
    ];


    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $selected_guide_page_id = $form_state->getValue('guide_page_select');
    $name = $form_state->getValue('name');

    $original_guide_page = Node::load($selected_guide_page_id);
    $current_guide =  $form_state->getValue('current_guide');

    $current_guide = Node::load($current_guide);

    if ($original_guide_page) {
      $cloned_guide_page = $original_guide_page->createDuplicate();
      $cloned_guide_page->setTitle($name);
      $cloned_guide_page->set('field_parent_guide', $current_guide->id());
      $cloned_guide_page->save();
      $form_state->setValue('cloned_guide_page', $cloned_guide_page->id());

      $alias = '/'. $current_guide->getTitle(). '/'. $cloned_guide_page->getTitle();

      // Check if the alias already exists.
      $path_alias_repository = \Drupal::service('path_alias.repository');
      $existing_alias = $path_alias_repository->lookupByAlias($alias, $cloned_guide_page->language()->getId());

      // If the alias does not exist or it's not the current node's alias, create or update it.
      if (!$existing_alias || $existing_alias['path'] !== '/node/' . $cloned_guide_page->id()) {
        // Create a new alias or update the existing one.
        $path_alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');
        $path_alias = $path_alias_storage->create([
          'path' => '/node/' . $cloned_guide_page->id(),
          'alias' => $alias,
          'langcode' => $cloned_guide_page->language()->getId(),
        ]);
        $path_alias->save();
      }

      $boxes = $this->getChildBoxes($original_guide_page->id());
      foreach ($boxes as $box) {
        $cloned_box = $box->createDuplicate();
        $cloned_box->set('field_parent_page', $cloned_guide_page->id()); // Update Parent Page reference
        $cloned_box->save();
      }
    }
  }

  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    // Create an array of AJAX commands.
    $response = new AjaxResponse();

    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal-form', $form));
      return $response;
    }
    $cloned_guide_page = $form_state->getValue('cloned_guide_page');
    $cloned_guide_page = Node::load($cloned_guide_page);

    // Close the modal dialog.
    $response->addCommand(new CloseModalDialogCommand());

    // Redirect to the cloned guide page.
    if ($cloned_guide_page) {
      $url = Url::fromRoute('entity.node.canonical', ['node' => $cloned_guide_page->id()]);
      $redirect_url = $url->toString();
      $response->addCommand(new RedirectCommand($redirect_url));
    }

    \Drupal::messenger()->addMessage($cloned_guide_page);
    return $response;

  }

  private function getGuidePageOptions() {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'guide_page')
      ->sort('title', 'ASC')
      ->accessCheck(TRUE);
    $result = $query->execute();
    $options = [];
    if (!empty($result)) {
      $nodes = Node::loadMultiple($result);
      foreach ($nodes as $node) {
        $options[$node->id()] = $node->getTitle();
      }
    }
    return $options;
  }

  // Retrieves guide boxes belonging to a guide page
  private function getChildBoxes($pageId) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'guide_box')
      ->condition('field_parent_page', $pageId)
      ->accessCheck(TRUE);
    $result = $query->execute();
    return Node::loadMultiple($result); // Assuming you have 'id()' on the box entity
  }

}

