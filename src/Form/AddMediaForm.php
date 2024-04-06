<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;

class AddMediaForm extends FormBase {

  public function getFormId(): string
  {
    return 'add_media_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form_helper = new FormHelper();

    $ids = (Object) [
      'current_node' => \Drupal::request()->query->get('current_node'),
      'current_box' => \Drupal::request()->query->get('current_box'),
      'current_item' => \Drupal::request()->query->get('current_item'),
    ];

    // Set the prefix, suffix, and hidden fields
    $form_helper->set_form_data($form, $ids, $this->getFormId());

    $current_item = property_exists($ids, 'current_item')? Node::load($ids->current_item) : null;
    $media = $current_item?->get('field_media_image')->entity;
    $edit = $current_item != null;

    // Link to upload more media
    $add_media_link = Link::fromTextAndUrl(
      $this->t('Create new Media'),
      Url::fromRoute('entity.media.collection')
    )->toRenderable();

    $add_media_link['#attributes'] = ['target' => '_blank'];

    $renderer = \Drupal::service('renderer');
    $add_media_link_html = $renderer->render($add_media_link);

    // Media select field
    $form['media'] = [
      '#type' => 'select',
      '#title' => $this->t('Media'),
      '#options' => $this->getMediaOptions(),
      '#required' => TRUE,
      '#description' => $add_media_link_html,
      '#default_value' => $edit? $media->id(): null,
      '#ajax' => [
        'callback' => '::mediaSelectedCallBack',
        'wrapper' => 'update-wrapper',
        'event' => 'change',
      ],
    ];

    $form['include_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Media Default name'),
      '#default_value' => !$edit || $current_item->label() == $media->getName(),
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
      '#default_value' => $edit? $current_item->label(): '',
    ];

    $form['update_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'update-wrapper'],
    ];

    // Draft mode Field
    $form['update_wrapper']['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Draft mode:'),
      '#description' => $this->t('Un-check this box to publish.'),
      '#default_value' => $edit ? $current_item->isPublished() == '0' || $media->isPublished() == '0': 0,
      '#disabled' => $edit && !$media->isPublished(),
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

  public function mediaSelectedCallBack(array &$form, FormStateInterface $form_state) {
    $selected = $form_state->getValue('media');
    $selected = Media::load($selected);

    if ($selected->isPublished()){
      unset($form['update_wrapper']['published']['#disabled']);
      $form['update_wrapper']['published']['#checked'] = false;
      $form['update_wrapper']['published']['#description'] = $this->t('Un-check this box to publish.');
    } else{
      $form['update_wrapper']['published']['#checked'] = true;
      $form['update_wrapper']['published']['#attributes']['disabled'] = true;
      $form['update_wrapper']['published']['#description'] = $this->t('Please publish the original node');
    }

    return $form['update_wrapper'];
  }

  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): \Drupal\Core\Ajax\AjaxResponse
  {
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'A Media item has been added.', '#'.$this->getFormId());
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $ajaxHelper = new FormHelper();

    if($form_state->getValue('current_item') == null){
      // Get the media
      $media = $form_state->getValue('media');
      $media = Media::load($media);

      // Create a link to it and add it to the box
      $item = $ajaxHelper->create_link($media, $form_state->getValue('current_box'));
      $item->set('title', $form_state->getValue('include_title') != '0'? $media->getName() : $form_state->getValue('title'));
      $item->set('status', $form_state->getValue('published') == '0');
      $item->save();

    } else {
      // Load the Link
      $current_item = $form_state->getValue('current_item');
      $current_item = Node::load($current_item);

      // Load the new Item
      $media = $form_state->getValue('media');
      $media = Media::load($media);

      // Update fields
      $current_item->set('title', $form_state->getValue('include_title') != '0'? $media->getName() : $form_state->getValue('title'));
      $current_item->set('status', $form_state->getValue('published') == '0');
      $current_item->set('field_media_image', $media);
      $current_item->save();
    }

    // Update Parents
    $ajaxHelper->updateParent($form, $form_state);
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getMediaOptions(): array
  {
    $options = [];
    $media_types = \Drupal::entityTypeManager()->getStorage('media_type')->loadMultiple();
    // Loop through all media types.
    foreach ($media_types as $media_type_id => $media_type) {
      // Get all media of type $media_type
      $query = \Drupal::entityQuery('media')
        ->condition('bundle', $media_type_id)
        ->accessCheck(False)
        ->sort('name', 'ASC');
      $media_ids = $query->execute();

      // Load all media entities of the type.
      $media_items = \Drupal::entityTypeManager()->getStorage('media')->loadMultiple($media_ids);

      if (!empty($media_items)) {
        // Create a group for each media type.
        $group_label = $media_type->label();
        $options[$group_label] = [];

        // Add the media to the group
        foreach ($media_items as $media_id => $media_item) {
          $options[$group_label][$media_id] = $media_item->label();
        }
      }
    }
    return $options;
  }
}
