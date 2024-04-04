<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class FormHelper {

  /**
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

  public function updateParent(array &$form, FormStateInterface $form_state,)
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

  public function set_form_data(array &$form, $ids, string $form_id){
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

  public function set_prefix(array &$form, string $id){
    $form['#prefix'] = '<div id="'. $id .'">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];
  }

  public function create_link(EntityInterface $new_content, String $current_box)
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

  public function update_link(array &$form, FormStateInterface $form_state, EntityInterface $current_item){
    $current_item->set('title', $form_state->getValue('title'));
    $current_item->set('status', $form_state->getValue('published') == '0');
    $current_item->set('changed', \Drupal::time()->getRequestTime());
    $current_item->save();
  }

  public function get_filled_field($current_item): string
  {
    $possible_fields = $this->get_fields();
    $field_to_delete = '';

    foreach ($possible_fields as $field_name) {
      if (!$current_item->get($field_name)->isEmpty()) {
        $field_to_delete = $field_name;
        break;
      }
    }

    return $field_to_delete;
  }

  public function get_fields(): array
  {
    return [
      'field_database_item',
      'field_html_item',
      'field_book_item',
      'field_media_image'
    ];
  }

  public function deletePages($parent, $delete_sub){
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

  public function delete_all_boxes($parent): void
  {
    $boxes = $parent->get('field_child_boxes')->referencedEntities();

    foreach($boxes as $box){
      if($parent->id() == $box->get('field_parent_node')->entity->id()){
        $this->delete_box($box);
      }
    }
  }

  public function delete_box($box): void {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'guide_page')
      ->condition('field_child_boxes', $box->id())
      ->accessCheck(TRUE);
    $result = $query->execute();

    foreach ($result as $page){
      $page = Node::load($page);
      $this->remove_child_box($page,$box);
    }

    $box?->delete();
  }

  public function remove_child_box($page, $box): void {
    $child_boxes = $page->get('field_child_boxes')->getValue();

    $child_boxes = array_filter($child_boxes, function ($box_new) use ($box) {
      return $box_new['target_id'] != $box->id();
    });

    $page->set('field_child_boxes', $child_boxes);
    $page->save();
  }

  public function update_child_pages(EntityInterface $parent, EntityInterface $page)
  {
    $page_list = $parent->get('field_child_pages')->getValue();
    $page_list[] = ['target_id' => $page->id()];

    $parent->set('field_child_pages', $page_list);
    $parent->set('changed', \Drupal::time()->getRequestTime());
    $parent->save();

  }

  public function get_position_options(String $guide_id): array
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
          $options[$group_label][$child_page->id()] = $child_page->label(); // Use the title or label of the page.
        }
      }
    }

    return $options;
  }
}
