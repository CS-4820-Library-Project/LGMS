<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a form to add or edit media entities.
 *
 * This form facilitates the addition of media to a site's content, allowing
 * users to select from existing media entities, configure titles, and manage
 * publication status.
 */
class AddMediaForm extends FormBase {

  /**
   * Checks if the user can edit their own article.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Drupal\Core\Session\AccountInterface $account
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(Request $request, AccountInterface $account) {
    $nid = $request->query->get('current_box');
    $node = Node::load($nid);

    if ($node && $node->getType() == 'guide_box' && $node->access('update')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'add_media_form';
  }

  /**
   * Builds the add/edit media form.
   *
   * @param array $form An associative array containing the structure of the form.
   * @param FormStateInterface $form_state The current state of the form.
   * @return array The form structure.
   */
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

    $form['update_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'update-wrapper'],
    ];

    if ($form_state->getValue('media') || $media){
      $form['update_wrapper']['include_title'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use Media Default name'),
        '#default_value' => !$edit || $current_item->label() == $media->getName(),
      ];

      $form['update_wrapper']['title'] = [
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

      // Draft mode Field
      $form['update_wrapper']['published'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Draft mode:'),
        '#description' => $this->t('Un-check this box to publish.'),
        '#default_value' => $edit ? $current_item->isPublished() == '0' || $media->isPublished() == '0': 0,
        '#disabled' => $edit && !$media->isPublished(),
      ];
    }


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
   * AJAX callback for media selection changes.
   *
   * Updates form elements dynamically based on the selected media entity, such
   * as enabling or disabling the publication status checkbox.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   * @return array The updated form elements.
   */
  public function mediaSelectedCallBack(array &$form, FormStateInterface $form_state) {
    $selected = $form_state->getValue('media');
    $selected = Media::load($selected);

    if (!$selected){
      return $form['update_wrapper'];
    }

    $is_document_type = ($selected->bundle() === 'document');

    $form['update_wrapper']['include_title']['#access'] = $is_document_type;
    $form['update_wrapper']['title']['#access'] = $is_document_type;

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
   * AJAX form submission handler.
   *
   * Processes the form submission via AJAX, improving user experience by
   * providing immediate feedback and avoiding full page reloads.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The current state of the form.
   * @return AjaxResponse An AJAX response to update the UI
   *                                         based on the form submission.
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    $ajaxHelper = new FormHelper();

    $message = 'A Media item has been added.';

    if ($form_state->getValue('current_item')){
      $message = 'A Media item has been edited.';
    }

    return $ajaxHelper->submitModalAjax($form, $form_state, $message, '#'.$this->getFormId());
  }

  /**
   * Submits the add/edit media form.
   *
   * Processes the submitted form values to either create a new media link
   * within the content structure or update an existing one.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $ajaxHelper = new FormHelper();

    if($form_state->getValue('current_item') == null){
      // Get the media
      $media = $form_state->getValue('media');
      $media = Media::load($media);

      $is_document_type = ($media->bundle() === 'document');

      // Create a link to it and add it to the box
      $item = $ajaxHelper->create_link($media, $form_state->getValue('current_box'));
      $item->set('title', $is_document_type? $form_state->getValue('include_title') != '0'? $media->getName() : $form_state->getValue('title') : $media->getName());
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
      $current_item->set('promote', 0);
      $current_item->set('field_media_image', $media);
      $current_item->save();
    }

    // Update Parents
    $ajaxHelper->updateParent($form, $form_state);
  }

  /**
   * Retrieves options for the media select element.
   *
   * Generates an options array for the media select element, grouping media
   * by type for easier selection in the form.
   *
   * @return array An associative array of media options, grouped by media type.
   * @throws InvalidPluginDefinitionException When the media type entity cannot be loaded.
   * @throws PluginNotFoundException When the media entity storage cannot be accessed.
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
