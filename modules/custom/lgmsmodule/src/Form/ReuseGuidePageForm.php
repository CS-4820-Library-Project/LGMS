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

class ReuseGuidePageForm extends FormBase {

  public function getFormId() {
    return 'lgmsmodule_reuse_guide_page_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $ids = null) {
    $form['select_page'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Page'),
      '#options' => $this->getGuidePageOptions($ids->current_guide_id),
      '#empty_option' => $this->t('- Select a Page -'),
      '#validated' => TRUE,
      '#required' => TRUE,
    ];

    $form['copy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Copy'),
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#states' => [
        'visible' => [
          ':input[name="copy"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#options' => $this->getPageList($ids->current_guide_id),
      '#required' => TRUE,
    ];

    $form['current_guide'] = [
      '#type' => 'hidden',
      '#value' =>  $ids->current_guide_id,
    ];

    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => NULL, // Initialize with NULL value
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reuse Guide Page'),
      '#button_type' => 'primary',
    ];

    $form['actions']['submit']['#ajax'] = [
      'callback' => '::submitAjax',
      'event' => 'click',
    ];


    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $guide = Node::load($form_state->getValue('current_guide'));
    $page = Node::load($form_state->getValue('select_page'));
    $parent = Node::load($form_state->getValue('position'));

    if($form_state->getValue('position')  == 'top_level')
      $parent = $guide;


    if($form_state->getValue('copy')){
      $new_page = $page->createDuplicate();

      $new_page->set('field_parent_guide', $parent);
      $new_page->set('title', $form_state->getValue('title'));
      $new_page->save();

      $boxes = $page->get('field_child_boxes')->referencedEntities();

      $new_box_list = [];
      foreach ($boxes as $box){
        $new_box = $box->createDuplicate();
        $new_box->set('field_parent_node', $new_page);
        $new_box->save();

        $items = $box->get('field_box_items')->referencedEntities();

        $new_items_list = [];
        foreach ($items as $item){
          $new_item = $item->createDuplicate();
          $new_item->set('field_parent_box', $new_box);

          if ($item->hasField('field_html_item') && !$item->get('field_html_item')->isEmpty()) {
            $html = $item->get('field_html_item')->entity;
            $html = $html->createDuplicate();

            $new_item->set('field_html_item', $html);

          } elseif ($item->hasField('field_database_item') && !$item->get('field_database_item')->isEmpty()) {
            $database = $item->get('field_database_item')->entity;
            $new_item->set('field_database_item', $database);

          } elseif ($item->hasField('field_media_image') && !$item->get('field_media_image')->isEmpty()) {
            $media = $item->get('field_media_image')->entity;
            $new_item->set('field_media_image', $media);
          }

          $new_item->save();
          $new_items_list[] = $new_item;
        }

        $new_box->set('field_box_items', $new_items_list);
        $new_box->save();

        $new_box_list[] = $new_box;
      }

      $new_page->set('field_child_boxes', $new_box_list);
      $new_page->save();

      $page = $new_page;

      $form_state->setValue('current_node', $page->id());
    } else {
      $new_page = Node::create([
        'type' => 'guide_page',
        'title' => $page->label(),
        'field_description' => $page->get('field_description'),
        'field_parent_guide' => $parent,
        'field_child_boxes' => $page->get('field_child_boxes')->referencedEntities(),
        'field_reference_node' => $page,
        'field_hide_description' => $page->get('field_hide_description'),
        'status' => $page->isPublished(),
      ]);

      $new_page->save();

      $page = $new_page;
      $form_state->setValue('current_node', $page->id());
    }

    $page_list = $parent->get('field_child_pages')->getValue();
    $page_list[] = ['target_id' => $page->id()];

    $parent->set('field_child_pages', $page_list);
    $parent->set('changed', \Drupal::time()->getRequestTime());
    $parent->save();
  }

  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    // Create an array of AJAX commands.
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Box created successfully.');
  }

  private function getGuidePageOptions($guide_id) {
    $options = [];

    // Fetch all guides.
    $guides = Node::loadMultiple(
      \Drupal::entityQuery('node')
        ->condition('type', 'guide')
        ->sort('title', 'ASC')
        ->accessCheck(True)
        ->execute()
    );

    foreach ($guides as $guide) {
      if ($guide->id() != $guide_id && $guide->hasField('field_child_pages')) {
        $child_pages_ids = array_column($guide->get('field_child_pages')->getValue(), 'target_id');
        $child_pages = !empty($child_pages_ids) ? Node::loadMultiple($child_pages_ids) : [];

        // Create an optgroup for the guide.
        $options[$guide->getTitle()] = [];

        foreach ($child_pages as $child_page) {
          // Add the child page under the guide.
          $options[$guide->getTitle()][$child_page->id()] = $child_page->getTitle();

          // Check if the child page has its own subpages.
          if ($child_page->hasField('field_child_pages')) {
            $subpages_ids = array_column($child_page->get('field_child_pages')->getValue(), 'target_id');
            $subpages = !empty($subpages_ids) ? Node::loadMultiple($subpages_ids) : [];

            // Label each subpage with the parent page title.
            foreach ($subpages as $subpage) {
              $label = '— ' . $child_page->getTitle() . ' subpage: ' . $subpage->getTitle();
              $options[$guide->getTitle()][$subpage->id()] = $label;
            }
          }
        }
      }
    }

    return $options;
  }

  public function getPageList($guide_id) {
    $options = [];

    $options['Top Level']['top_level'] = t('Top Level');

    // Load the guide entity.
    $guide = Node::load($guide_id);

    // Check if the guide has been loaded and has the field_child_pages field.
    if ($guide && $guide->hasField('field_child_pages')) {
      // Get the array of child page IDs from the guide.
      $child_pages = $guide->get('field_child_pages')->referencedEntities();

      if (!empty($child_pages)) {
        // Group label for child pages.
        $group_label = 'Sub page of';

        // Initialize the group if it's not set.
        if (!isset($options[$group_label])) {
          $options[$group_label] = [];
        }

        // Create options array from the child pages.
        foreach ($child_pages as $child_page) {
          if ($child_page->get('field_parent_guide')->entity->id() == $guide_id)
            $options[$group_label][$child_page->id()] = $child_page->label(); // Use the title or label of the page.
        }
      }
    }

    // Return the options array with the 'Top Level' and the grouped child pages.
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