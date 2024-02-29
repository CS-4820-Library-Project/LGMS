<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

class AddImageForm extends FormBase {

  public function getFormId() {
    return 'add_image_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    $current_box = \Drupal::request()->query->get('current_box');
    $form['current_box'] = [
      '#type' => 'hidden',
      '#value' => $current_box,
    ];

    $current_node = \Drupal::request()->query->get('current_node');
    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $current_node,
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item Title:'),
      '#required' => TRUE,
    ];


    // Body field
    $form['image'] = [
      '#title' => $this->t('Upload Image'),
      '#type' => 'managed_file',
      '#upload_location' => 'public://uploaded_images/',
      '#required' => TRUE,
      '#description' => $this->t('Upload an image file.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg jpeg png gif'],
      ],
    ];

    $form['alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alternative Text:'),
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

    return $ajaxHelper->submitModalAjax($form, $form_state, 'an Image has been added.');
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
    $current_node = $form_state->getValue('current_node');
    $current_node = Node::load($current_node);

    $current_box = $form_state->getValue('current_box');
    $current_box = Node::load($current_box);

    $image = $form_state->getValue('image');
    $file = File::load(reset($image));

    $new_node = Node::create([
      'type' => 'guide_item',
      'title' => $form_state->getValue('title'),
      'field_image_box_item' => [
        'target_id' => $file->id(),
        'alt' => $form_state->getValue('alt'),
      ],
    ]);

    $new_node->save();

    $boxList = $current_box->get('field_box_items')->getValue();
    $boxList[] = ['target_id' => $new_node->id()];

    $current_box->set('field_box_items', $boxList);
    $current_box->save();

    $ajaxHelper = new FormHelper();
    $ajaxHelper->updateParent($form, $form_state);
  }
}
