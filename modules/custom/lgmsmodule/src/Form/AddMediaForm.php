<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;

class AddMediaForm extends FormBase {

  public function getFormId() {
    return 'add_media_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_helper = new FormHelper();
    $form_helper->set_prefix($form,  $this->getFormId());

    $current_box = \Drupal::request()->query->get('current_box');
    $current_node = \Drupal::request()->query->get('current_node');
    $current_item = \Drupal::request()->query->get('current_item');

    $form['current_box'] = [
      '#type' => 'hidden',
      '#value' => $current_box,
    ];

    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $current_node,
    ];

    $form['current_item'] = [
      '#type' => 'hidden',
      '#value' => $current_item,
    ];

    $current_item = Node::load($current_item);
    $media = $current_item?->get('field_media_image')->entity;
    $edit = $current_item != null;

    $add_media_link = Link::fromTextAndUrl(
      $this->t('Create new Media'),
      Url::fromRoute('entity.media.collection')
    )->toRenderable();

    $add_media_link['#attributes'] = ['target' => '_blank'];

    $renderer = \Drupal::service('renderer');
    $add_media_link_html = $renderer->render($add_media_link);

    $form['media'] = [
      '#type' => 'select',
      '#title' => $this->t('Media'),
      '#options' => $this->getMediaOptions(),
      '#required' => TRUE,
      '#description' => $add_media_link_html,
      '#default_value' => $edit? $media->id(): null,
    ];

    $form['include_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Media Default name'),
      '#default_value' => !$edit || $current_item->label() == 'Media Item',
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Media Title:'),
      '#states' => [
        'invisible' => [
          ':input[name="include_title"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="include_title"]' => ['checked' => False],
        ],
      ],
      '#default_value' => $edit? $current_item->label() != 'Media Item'? $current_item->label(): '' : '',
    ];


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
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'A Media item has been added.', '#'.$this->getFormId());
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ajaxHelper = new FormHelper();

    if($form_state->getValue('current_item') == null){
      // Get the media
      $media = $form_state->getValue('media');
      $media = Media::load($media);

      // Create a link to it and add it to the box
      $item = $ajaxHelper->create_link($media, $form_state->getValue('current_box'));
      $item->set('title', $form_state->getValue('include_title') != '0'? 'Media Item' : $form_state->getValue('title'));

    } else {
      // Load the Link
      $current_item = $form_state->getValue('current_item');
      $current_item = Node::load($current_item);

      // Load the new Item
      $media = $form_state->getValue('media');
      $media = Media::load($media);

      // Update fields
      $ajaxHelper->update_link($form, $form_state, $current_item);
      $current_item->set('title', $form_state->getValue('include_title') != '0'? 'Media Item' : $form_state->getValue('title'));
      $current_item->set('field_media_image', $media);
      $current_item->save();
    }

    // Update Parents
    $ajaxHelper->updateParent($form, $form_state);
  }

  public function getMediaOptions() {
    $options = [];
    $media_types = \Drupal::entityTypeManager()->getStorage('media_type')->loadMultiple();
    // Loop through all media types.
    foreach ($media_types as $media_type_id => $media_type) {
      $query = \Drupal::entityQuery('media')
        ->condition('bundle', $media_type_id)
        ->accessCheck(False)
        ->sort('name', 'ASC');  // Sorting by name/title of media, adjust as needed.
      $media_ids = $query->execute();
      // Load all media entities of the type.
      $media_items = \Drupal::entityTypeManager()->getStorage('media')->loadMultiple($media_ids);

      if (!empty($media_items)) {
        // Create a group for each media type.
        $group_label = $media_type->label();
        $options[$group_label] = [];
        foreach ($media_items as $media_id => $media_item) {
          $options[$group_label][$media_id] = $media_item->label();
        }
      }
    }
    return $options;
  }
}
