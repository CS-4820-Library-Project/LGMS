<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides helper functions for form operations in the LGMS module.
 *
 * This class includes methods for AJAX responses, updating parent nodes,
 * setting form data, and more, aiding in the manipulation and handling of form
 * submissions and operations within the LGMS (Library Guide Management System) module.
 */
class FormHelper {

  /**
   * Handles AJAX submissions and redirects or updates the page accordingly.
   *
   * @param array &$form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The state of the form.
   * @param string $message
   *   The message to display upon successful submission.
   * @param string $form_id
   *   The CSS ID of the form element, defaults to '#modal-form'.
   *
   * @return AjaxResponse
   *   The AJAX response to be returned.
   *
   * @throws EntityMalformedException
   */
  public function submitModalAjax(array &$form, FormStateInterface $form_state, String $message, $form_id = '#modal-form'): AjaxResponse
  {
    // Create an array of AJAX commands.
    $response = new AjaxResponse();

    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand($form_id, $form));
      return $response;
    }

    // Close the modal dialog.
    $response->addCommand(new CloseModalDialogCommand());

    $curr_node = $form_state->getValue('current_node');
    $curr_node = Node::load($curr_node);

    if ($curr_node == NULL) {
      $dashboardUrl = Url::fromRoute('lgmsmodule.dashboard_overview')->toString();
      $response->addCommand(new RedirectCommand($dashboardUrl));
      return $response;
    }

    $curr_node_url = $curr_node->toUrl()->toString();
    $curr_node_url = str_ireplace('LGMS/', '', $curr_node_url);

    $response->addCommand(new RedirectCommand(Url::fromUri('internal:' . $curr_node_url)->toString()));

    \Drupal::messenger()->addMessage($message);
    return $response;
  }

  /**
   * Updates the 'changed' timestamp of the parent node and potentially its parent guide.
   *
   * @param array &$form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The state of the form.
   *
   * @throws EntityStorageException
   */
  public function updateParent(array &$form, FormStateInterface $form_state,): void
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

  /**
   * Sets initial data for forms, including hidden fields for IDs and AJAX wrappers.
   *
   * @param array &$form
   *   The form array.
   * @param object $ids
   *   An object containing identifiers required for the form.
   * @param string $form_id
   *   The form ID used for setting prefixes and suffixes.
   */
  public function set_form_data(array &$form, $ids, string $form_id): void
  {
    $this->set_prefix($form,$form_id);

    foreach ($ids as $label => $id){
      // if any of the fields is missing, deny access to the form
      if ($label != 'current_item' && (empty($id) || !Node::load($id))){
        throw new AccessDeniedHttpException();
      }

      $form[$label] = [
        '#type' => 'hidden',
        '#value' => $id,
      ];
    }
  }

  /**
   * Sets the AJAX wrapper prefixes and suffixes for the form.
   *
   * @param array &$form
   *   The form array.
   * @param string $id
   *   The form ID to use as the AJAX wrapper ID.
   */

  public function set_prefix(array &$form, string $id): void
  {
    $form['#prefix'] = '<div id="'. $id .'">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];
  }

  /**
   * Creates a new link entity from provided content and associates it with a box.
   *
   * @param EntityInterface $new_content
   *   The new content entity to link.
   * @param string $current_box
   *   The ID of the box to link the content to.
   *
   * @return EntityInterface
   *   The newly created link entity.
   *
   * @throws EntityStorageException
   */

  public function create_link(EntityInterface $new_content, String $current_box): EntityInterface
  {
    //find what item is being created
    $field_name = '';
    if ($new_content->bundle() == 'guide_html_item'){
      $field_name = 'field_html_item';
    } elseif ($new_content->bundle() == 'guide_book_item'){
      $field_name = 'field_book_item';
    } elseif ($new_content->bundle() == 'guide_database_item'){
      $field_name = 'field_database_item';
    } elseif ($new_content->getEntityTypeId() == 'media'){
      $field_name = 'field_media_image';
    }

    // Create the item
    $new_item = Node::create([
      'type' => 'guide_item',
      'title' => $new_content->label(),
      $field_name => $new_content,
      'field_parent_box' => $current_box,
      'status' => $new_content->isPublished(),
      'promote' => 0,
    ]);

    $new_item->save();

    // Update the box item list
    $current_box = Node::load($current_box);
    $boxList = $current_box->get('field_box_items')->getValue();
    $boxList[] = ['target_id' => $new_item->id()];

    $current_box->set('field_box_items', $boxList);
    $current_box->save();

    if ($field_name != 'field_media_image'){
      $new_content->set('field_parent_item',$new_item);
      $new_content->save();
    }

    return $new_item;
  }

  /**
   * Updates the content link with new information from form submission.
   *
   * @param array &$form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   * @param EntityInterface $current_item
   *   The content link entity being updated.
   *
   * @throws EntityStorageException
   */
  public function update_link(array &$form, FormStateInterface $form_state, EntityInterface $current_item): void
  {
    $current_item->set('title', $form_state->getValue('title'));
    $current_item->set('status', $form_state->getValue('published') == '0');
    $current_item->set('promote', 0);
    $current_item->set('changed', \Drupal::time()->getRequestTime());
    $current_item->save();
  }

  /**
   * Determines the field of the current item that is filled.
   *
   * @param $current_item
   *   The current item entity to check.
   * @return string
   *   The field name that is filled for the current item.
   */

  public function get_filled_field($current_item): string
  {
    $possible_fields = $this->get_content_items();
    $filled_field = '';

    foreach ($possible_fields as $field_name) {
      if (!$current_item->get($field_name)->isEmpty()) {
        $filled_field = $field_name;
        break;
      }
    }

    return $filled_field;
  }

  /**
   * Provides a list of content item field names.
   *
   * @return array
   *   An array of field names for content items.
   */

  public function get_content_items(): array
  {
    return [
      'field_database_item',
      'field_html_item',
      'field_book_item',
      'field_media_image'
    ];
  }

  /**
   * Deletes the specified pages and optionally their subpages.
   *
   * @param EntityInterface $parent
   *   The parent entity from which pages will be deleted.
   * @param bool $delete_sub
   *   Whether to delete subpages of the specified pages.
   * @throws EntityStorageException
   */
  public function deletePages($parent, $delete_sub): void
  {
    $this->delete_all_boxes($parent);

    if($delete_sub) {
      $pages = $parent->get('field_child_pages')->referencedEntities();
      foreach ($pages as $page) {
        $this->delete_all_boxes($page);
        $this->deletePages($page, $delete_sub);
      }
    }
    $parent->delete();
  }

  /**
   * Deletes all boxes associated with a parent entity.
   *
   * @param EntityInterface $parent
   *   The parent entity from which boxes will be deleted.
   */
  public function delete_all_boxes($parent): void
  {
    $boxes = $parent->get('field_child_boxes')->referencedEntities();

    foreach($boxes as $box){
      if($parent->id() == $box->get('field_parent_node')->entity->id()){
        $this->delete_box($box);
      }
    }
  }

  /**
   * Deletes a specific box and its content.
   *
   * @param EntityInterface $box
   *   The box entity to delete.
   */
  public function delete_box($box): void {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'guide_page')
      ->condition('field_child_boxes', $box->id())
      ->accessCheck(TRUE);
    $result = $query->execute();

    foreach ($result as $page){
      $page = Node::load($page);
      $this->remove_child($page,$box,'field_child_boxes');
    }

    $box?->delete();
  }

  /**
   * Removes a child entity from a parent's field.
   *
   * @param $page
   * @param EntityInterface $child_to_remove
   *   The child entity to remove.
   * @param string $field
   *   The field from which the child will be removed.
   */
  public function remove_child($page, $child_to_remove, $field): void {
    $children = $page->get($field)->getValue();

    $children = array_filter($children, function ($child) use ($child_to_remove) {
      return $child['target_id'] != $child_to_remove->id();
    });

    $page->set($field, $children);
    $page->save();
  }

  /**
   * Adds a child entity to a parent's field.
   *
   * @param EntityInterface $parent
   *   The parent entity.
   * @param EntityInterface $child
   *   The child entity to add.
   * @param string $field
   *   The field to which the child will be added.
   * @throws EntityStorageException
   */
  public function add_child(EntityInterface $parent, EntityInterface $child, $field): void
  {
    $page_list = $parent->get($field)->getValue();
    $page_list[] = ['target_id' => $child->id()];

    $parent->set($field, $page_list);
    $parent->save();

  }

  public function get_position_options(FormStateInterface $form_state, String $guide_id, bool $onlyWithSubpages = false): array
  {
    $options = [];

    if (!$guide_id){
      return $options;
    }

    $options['Page Level'][$guide_id] = t('Page Level');

    // Load the guide entity.
    $guide = Node::load($guide_id);

    // Check if the guide has been loaded and has the field_child_pages field.
    if ($guide && $guide->hasField('field_child_pages')) {
      // Get the array of child page IDs from the guide.
      $child_pages = $guide->get('field_child_pages')->referencedEntities();

      if (!empty($child_pages)) {
        // Group label for child pages.
        $group_label = 'Sub-page Level';

        // Initialize the group if it's not set.
        if (!isset($options[$group_label])) {
          $options[$group_label] = [];
        }

        // Create options array from the child pages.
        foreach ($child_pages as $child_page) {
          // Do not show the selected page as an option
          if ($child_page->id() == $form_state->getValue('select_page')){
            continue;
          }

          if ($child_page->hasField('field_reference_node') && $child_page->get('field_reference_node')->isEmpty()){
            if ($onlyWithSubpages){
              if ($child_page->hasField('field_child_pages')) {
                $sub_child_pages = $child_page->get('field_child_pages')->referencedEntities();
                if (!empty($sub_child_pages)) {
                  $options[$child_page->id()] = $child_page->label();
                }
              }
            } else {
              $options[$group_label][$child_page->id()] = $child_page->label();
            }
          }
        }
      }
    }

    return $options;
  }

  public function get_pages_options(String $guide_id, bool $include_guide = true): array
  {
    $options = [];
    // Load the guide entity.
    $guide = Node::load($guide_id);

    // Check if the guide has been loaded and has the field_child_pages field.
    if ($guide && $guide->hasField('field_child_pages')) {
      // Guide Option
      if ($include_guide){
        $options['Guide'][$guide_id] = t('Entire Guide');
      }

      // Get the array of child page IDs from the guide.
      $child_pages = $guide->get('field_child_pages')->referencedEntities();

      if (!empty($child_pages)) {
        // Group label for child pages.
        $group_label = 'Pages';

        // Initialize the group if it's not set.
        if (!isset($options[$group_label])) {
          $options[$group_label] = [];
        }

        // Create options array from the child pages.
        foreach ($child_pages as $child_page) {
          $options[$group_label][$child_page->id()] = $child_page->label();

          if ($child_page->get('field_parent_guide')->entity->id() == $guide_id) {
            // Check if the child page has its own subpages.
            if ($child_page->hasField('field_child_pages')) {
              $subpages_ids = array_column($child_page->get('field_child_pages')->getValue(), 'target_id');
              $subpages = !empty($subpages_ids) ? Node::loadMultiple($subpages_ids) : [];

              // Label each subpage with the parent page title.
              foreach ($subpages as $subpage) {
                $label = '— ' .  $subpage->getTitle();
                $options[$group_label][$subpage->id()] = $label;
              }
            }
          }
        }
      }
    }

    // Return the options array with the 'Top Level' and the grouped child pages.
    return $options;
  }

  /**
   * Generates the reorder table for entities.
   *
   * @param array &$form The form array to append the reorder table to.
   * @param mixed $list The list of entities to be reordered.
   */
  public function get_reorder_table(array &$form, $list): void
  {
    $form['pages_table'] = [
      '#type' => 'table',
      '#header' => ['Title', 'Weight'],
      '#tabledrag' => [[
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'pages-order-weight',
      ]],
    ];

    foreach ($list as $weight => $item) {
      $loaded_item = Node::load($item->target_id);

      $form['pages_table'][$weight]['#attributes']['class'][] = 'draggable';
      $form['pages_table'][$weight]['title'] = [
        '#markup' => $loaded_item->label(),
      ];

      $form['pages_table'][$weight]['weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => ['class' => ['pages-order-weight']],
      ];
    }
  }

  /**
   * Processes the reordered list to update the entity storage accordingly.
   *
   * @param array $values The form values containing the new order.
   * @param array $items The original items to reorder.
   * @return array The reordered items.
   */
  public function get_new_order($values, $items): array
  {
    $reordered_items = [];

    foreach ($values as $id => $value) {
      if (isset($items[$id])) {
        $reordered_items[$value['weight']] = $items[$id];
      }
    }

    ksort($reordered_items);

    return $reordered_items;
  }

  /**
   * Generates a list of options for entity reference fields, optionally grouped.
   *
   * @param string $content_type
   *   The content type of entities to include.
   * @param string $group_by
   *   (optional) The field by which to group the options.
   *
   * @return array
   *   An array of options for entity reference fields.
   */
  public function get_item_options(String $content_type, String $group_by = ''): array
  {
    $query = \Drupal::entityQuery('node')
      ->condition('type', $content_type)
      ->sort('title', 'ASC')
      ->accessCheck(False);
    $items_ids = $query->execute();

    // Load all the items
    $nodes = Node::loadMultiple($items_ids);
    $options = [];

    // Add them to the options
    foreach ($nodes as $node) {
      if (!empty($group_by)){
        $parent = $node->get($group_by)->entity;

        if ($parent && $parent->bundle() == 'guide_page'){
          $parent = $parent->get('field_parent_guide')->entity;
        }

        if ($parent) {
          $options[$parent->label()][$node->id()] = $node->label();
        } else {
          $options['Uncategorized'][$node->id()] = $node->label();
        }

      } else{
        $options[$node->id()] = $node->label();
      }
    }

    if (!empty($group_by)) {
      ksort($options);
    }

    // Return the options
    return $options;
  }

  /**
   * Clones the pages from one guide to another, optionally creating references.
   *
   * @param EntityInterface $parent The source guide.
   * @param EntityInterface $new_parent The destination guide.
   * @param bool $ref Whether to create references instead of duplicates.
   * @throws EntityStorageException
   */

  public function clone_pages($parent, $new_parent, bool $ref = false): void
  {
    $pages = $parent->get('field_child_pages')->referencedEntities();

    $new_page_list = [];

    foreach ($pages as $page) {
      $cloned_page = $page->createDuplicate();
      $cloned_page->set('promote', 0);
      $cloned_page->set('field_parent_guide', $new_parent);

      if ($ref){
        $cloned_page->set('field_reference_node', $page);
      }

      $cloned_page->setOwnerId(\Drupal::currentUser()->id());
      $cloned_page->save();

      $this->clone_boxes($page, $cloned_page);
      $this->clone_pages($page, $cloned_page);

      $new_page_list[] = ['target_id' => $cloned_page->id()];
    }

    // After cloning all boxes, update the cloned guide with the list of cloned boxes.
    if (!empty($new_page_list)) {
      $new_parent->set('field_child_pages', $new_page_list);
      $new_parent->save();
    }
  }

  /**
   * Clones all boxes from one guide/page to another.
   *
   * @param EntityInterface $page The source page.
   * @param EntityInterface $new_page The destination page.
   * @throws EntityStorageException
   */
  public function clone_boxes($page, $new_page): void
  {
    $guide_boxes = $page->get('field_child_boxes')->referencedEntities();

    $new_box_list = [];

    foreach ($guide_boxes as $box) {
      if ($box->hasField('field_parent_node') && $box->get('field_parent_node')->entity->id() != $page->id()){
        $new_box_list[] = ['target_id' => $box->id()];
      } else {
        $cloned_box = $box->createDuplicate();
        $cloned_box->set('field_parent_node', $new_page->id());
        $cloned_box->set('promote', 0);
        $cloned_box->save();

        $new_box_list[] = ['target_id' => $cloned_box->id()];

        $new_items_list = [];
        $items = $box->get('field_box_items')->referencedEntities();

        foreach ($items as $item){
          // Create a copy of the item and update it's owner
          $new_item = $item->createDuplicate();
          $new_item->set('field_parent_box', $cloned_box);
          $new_item->set('promote', 0);
          $new_item->set('field_lgms_reference', TRUE);
          $new_item->setOwnerId(\Drupal::currentUser()->id());

          // Add the item to the list
          $new_item->save();
          $new_items_list[] = $new_item;
        }

        // Save the list of items
        $cloned_box->set('field_box_items', $new_items_list);
        $cloned_box->setOwnerId(\Drupal::currentUser()->id());
        $cloned_box->save();
      }
    }

    // After cloning all boxes, update the cloned guide with the list of cloned boxes.
    if (!empty($new_box_list)) {
      $new_page->set('field_child_boxes', $new_box_list);
      $new_page->save();
    }
  }
}
