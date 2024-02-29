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

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    $current_node = \Drupal::request()->query->get('current_node');
    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $current_node,
    ];

    $form['#attributes']['id'] = 'form-selector';
/*
    $form['tabs'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['first_tab'] = [
      '#type' => 'details',
      '#title' => $this->t('Create a new Box'),
      '#group' => 'tabs',
    ];

    // Title field
    $form['first_tab']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Box Title:'),
      '#required' => TRUE,
    ];


    // Body field
    $form['first_tab']['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
    ];

    $form['second_tab'] = [
      '#type' => 'details',
      '#title' => $this->t('Reuse A Box'),
      '#group' => 'tabs',
    ];

    $form['second_tab']['box'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Box Name'),
      '#target_type' => 'node', // Adjust according to your needs
      '#selection_settings' => [
        'target_bundles' => ['guide_box'], // Adjust to your guide page bundle
      ],
      '#required' => TRUE,
    ];

    $form['second_tab']['reference'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<Strong>Reference:</Strong> By selecting this, a reference of the box will be created. it will be un-editable from this guide/page'),
    ];

    $form['second_tab']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Box Title:'),
      '#required' => TRUE,
      '#states' => [
        'invisible' => [
          ':input[name="reference"]' => ['checked' => TRUE],
        ],
      ],
    ];*/

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Box Title:'),
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    $form['actions']['submit']['#ajax'] = [
      'callback' => '::submitAjax',
      'event' => 'click',
    ];

    return $form;
  }

  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box created successfully.');
  }


  public static function hideTextFormatHelpText(array $element, FormStateInterface $form_state) {
    if (isset($element['format']['help'])) {
      $element['format']['help']['#access'] = FALSE;
    }
    if (isset($element['format']['guidelines'])) {
      $element['format']['guidelines']['#access'] = FALSE;
    }
    if (isset($element['format']['#attributes']['class'])) {
      unset($element['format']['#attributes']['class']);
    }
    return $element;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $curr_node = $form_state->getValue('current_node');
    $curr_node = Node::load($curr_node);
    $nid = $curr_node->id();


    if ($curr_node->bundle() === 'guide'){
      // Get the list of guide pages
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'guide_page')
        ->condition('field_parent_guide', $curr_node->id())
        ->accessCheck(TRUE);
      $result = $query->execute();

      // Get the first page
      $first_node_id = reset($result);
      $page = Node::load($first_node_id);

      $nid = $page->id();
    }

    $new_node = Node::create([
      'type' => 'guide_box',
      'title' => $form_state->getValue('title'),
      'field_parent_page' => ['target_id' => $nid],
    ]);

    $new_node->save();

    $page = Node::load($nid);
    $boxList = $page->get('field_child_boxes')->getValue();
    $boxList[] = ['target_id' => $new_node->id()];

    $page->set('field_child_boxes', $boxList);
    $page->save();

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);

    /*$curr_node = $form_state->getValue('current_node');
    $curr_node = Node::load($curr_node);
    $nid = $curr_node->id();


    if ($curr_node->bundle() === 'guide'){
      // Get the list of guide pages
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'guide_page')
        ->condition('field_parent_guide', $curr_node->id())
        ->accessCheck(TRUE);
      $result = $query->execute();

      // Get the first page
      $first_node_id = reset($result);
      $page = Node::load($first_node_id);

      $nid = $page->id();
    }

    if(!$form->getValue(['second_tab', 'reference'])){
    $original_box = Node::load($form->getValue(['second_tab', 'box']);
    $new_box = $original_box.duplicate();
    $new_box->set('field_parent_page', $nid);
    $new_box->set('title', $form->getValue(['second_tab', 'title']);

    $new_box->save();
   } else {

   }


    \Drupal::messenger()->addMessage('Box created successfully.');

    $curr_node_url = $curr_node->toUrl()->toString();
    $curr_node_url = str_replace('lgms/', '', $curr_node_url);

    $node_path = str_replace('lgms/', '', $curr_node_url);

    $form_state->setRedirectUrl(Url::fromUri('internal:' . $node_path));*/
  }
}
