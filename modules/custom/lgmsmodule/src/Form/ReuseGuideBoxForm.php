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

class ReuseGuideBoxForm extends FormBase {

  public function getFormId() {
    return 'reuse_guide_box_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $ids = null) {
    $form['#prefix'] = '<div id="' . $this->getFormId() . '">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $ids->current_node,
    ];

    $form['#attributes']['id'] = 'form-selector';

    $form['box'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Box Title'),
      '#target_type' => 'node', // Adjust according to your needs
      '#selection_settings' => [
        'target_bundles' => ['guide_box'], // Adjust to your guide page bundle
      ],
      '#required' => TRUE,
    ];

    $form['reference'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<Strong>Reference:</Strong> By selecting this, a reference of the box will be created. it will be un-editable from this guide/page'),
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Box Title:'),
      '#states' => [
        'invisible' => [
          ':input[name="reference"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['#validate'][] = '::validateFields';

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

  public function validateFields(array &$form, FormStateInterface $form_state) {
    $reference = $form_state->getValue('reference');
    $title = $form_state->getValue('title');
    if (!$reference && empty($title)) {
      $form_state->setErrorByName('title', $this->t('Box Title: field is required.'));
    }
  }

  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box created successfully.', '#'.$this->getFormId());
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

    $box = Node::load($form_state->getValue('box'));

    if(!$form_state->getValue('reference')){
      $new_box = $box->createDuplicate();
      $new_box->set('field_parent_node', $nid);
      $new_box->set('title', $form_state->getValue('title'));
      $new_box->setOwnerId(\Drupal::currentUser()->id());
      $new_box->save();

      $items = $box->get('field_box_items')->referencedEntities();

      $new_items_list = [];
      foreach ($items as $item){
        $new_item = $item->createDuplicate();
        $new_item->set('field_parent_box', $new_box);
        $new_item->setOwnerId(\Drupal::currentUser()->id());

        if ($item->hasField('field_html_item') && !$item->get('field_html_item')->isEmpty()) {
          $html = $item->get('field_html_item')->entity;
          $html = $html->createDuplicate();
          $html->setOwnerId(\Drupal::currentUser()->id());
          $html->save();

          $new_item->set('field_html_item', $html);

        } elseif ($item->hasField('field_database_item') && !$item->get('field_database_item')->isEmpty()) {
          $database = $item->get('field_database_item')->entity;
          $new_item->set('field_database_item', $database);

        } elseif ($item->hasField('field_book_item') && !$item->get('field_book_item')->isEmpty()) {
          $book = $item->get('field_book_item')->entity;
          $book = $book->createDuplicate();
          $book->setOwnerId(\Drupal::currentUser()->id());
          $book->save();

          $new_item->set('field_book_item', $book);
        } elseif ($item->hasField('field_media_image') && !$item->get('field_media_image')->isEmpty()) {
          $media = $item->get('field_media_image')->entity;
          $new_item->set('field_media_image', $media);
        }

        $new_item->save();
        $new_items_list[] = $new_item;
      }

      $new_box->set('field_box_items', $new_items_list);
      $new_box->save();

      $box = $new_box;
    }

    $page = Node::load($nid);
    $boxList = $page->get('field_child_boxes')->getValue();
    $boxList[] = ['target_id' => $box->id()];

    $page->set('field_child_boxes', $boxList);
    $page->save();

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
