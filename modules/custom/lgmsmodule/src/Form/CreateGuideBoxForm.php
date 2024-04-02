<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class CreateGuideBoxForm extends FormBase {

  public function getFormId() {
    return 'create_guide_box_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $ids = null) {
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
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box created successfully.', '#'.$this->getFormId());
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
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

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
