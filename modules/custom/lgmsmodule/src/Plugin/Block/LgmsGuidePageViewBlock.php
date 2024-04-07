<?php

namespace Drupal\lgmsmodule\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Serialization\Json;
use Drupal\node\Entity\Node;

/**
 *
 *
 * @Block(
 *   id = "page_view_block",
 *   admin_label = @Translation("LGMS Page View Block"),
 *   category = @Translation("LGMS")
 * )
 */
class LgmsGuidePageViewBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Get the current guide
    $current_guide_id = $this->getCurrentGuideId();

    // Make sure the block is placed on a guide or guide page
    if (!$current_guide_id){
      return $build;
    }

    $current_guide = Node::load($current_guide_id);

    // The url for the guide
    $current_guide_url = $current_guide->toUrl()->toString();
    $current_guide_url = str_ireplace('LGMS/', '', $current_guide_url);

    $url = Url::fromUri('internal:' . $current_guide_url);
    $link = Link::fromTextAndUrl( $current_guide->label(), $url)->toRenderable();

    $build['#title'] = $link;

    // Add a list to hold the page list to your block.
    $build['guide_container']['content'] = [
      '#theme' => 'item_list',
      '#items' => [],
      '#attributes' => ['style' => 'margin-left: 10px;'],
    ];

    $pages = $current_guide->get('field_child_pages')->referencedEntities();

    foreach ($pages as $page) {
      $class = '';

      // check if the page is published and user is authenticated
      if($page->isPublished() == 0){
        if(!\Drupal::currentUser()->isAuthenticated()){
          continue;
        }
        $class = 'node--unpublished';
      }

      // Retrieve sub-pages for the current page.
      $sub_pages = $page->get('field_child_pages')->referencedEntities();

      // Get the link to the page
      $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $page->id()]);
      $link = \Drupal\Core\Link::fromTextAndUrl($page->label(), $url)->toString();

      // add a link to the current page to the list
      $page_item['wrapper'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['class' => ['page-item-wrapper']], // Ensure this class is defined in your CSS
        'page' => [
          '#markup' => '<div class="' . $class . '">' . $link . '</div>',
          'sub_pages' => [
            '#theme' => 'item_list',
            '#items' => [],
            'icons'=>[]
          ],
        ],
      ];

      // Add sub-pages to the list if they exist.
      if (!empty($sub_pages) && $page->get('field_parent_guide')->entity->id() == $current_guide->id()) {
        foreach ($sub_pages as $sub_page) {
          $sub_page_class = '';

          // check if the page is published and user is authenticated
          if($sub_page->isPublished() == 0){
            if(!\Drupal::currentUser()->isAuthenticated()){
              continue;
            }
            $sub_page_class = 'node--unpublished';
          }

          // Get the link to the subpage
          $sub_url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $sub_page->id()]);
          $sub_link = \Drupal\Core\Link::fromTextAndUrl($sub_page->label(), $sub_url)->toString();

          // add a link to the current subpage to the list
          $page_item['sub_pages']['#items'][] = [
            '#markup' => '<div class="' . $sub_page_class . ' sub-page-item">' . $sub_link . '</div>',
          ];
        }
      }

      // Add the page item to the guide list.
      $build['guide_container']['content']['#items'][] = $page_item;
      $page_item = [];
    }

    if (\Drupal::currentUser()->hasPermission('create guide_page content')) {
      // Generate the URL for the custom form route, including the query parameter for the current guide.
      $array_of_objects = [(object)['name' => 'Create Guide Page', 'form' => 'CreateGuidePageForm'],(object) ['name' => 'Reuse Guide Page', 'form' => 'ReuseGuidePageForm']];
      $json_data = json_encode($array_of_objects);
      $query_param = urlencode($json_data);

      // Store the current guide id and encode it
      $ids = ['current_guide' => $current_guide_id];
      $json_data = json_encode($ids);
      $ids = urlencode($json_data);

      $url = Url::fromRoute('lgmsmodule.popup_modal', [], ['query' => ['ids' => $ids, 'forms' => $query_param]]);

      // Create the link render array with AJAX attributes.
      $link = Link::fromTextAndUrl(t('Create/Reuse Guide Page'), $url)->toRenderable();
      $link['#attributes'] = [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode(['width' => 800]),
        'style' => 'margin-left: 16px;'
      ];

      // Render the link somewhere in your build array.
      $build['guide_page_modal'] = $link;

      $re_order_url = Url::fromRoute('re_order_page.form', [], ['query' => ['guide_id' => $current_guide_id, 'current_node' => \Drupal::routeMatch()->getParameter('node')->id()]]);
      $re_order_link = Link::fromTextAndUrl(t('Re-Order Pages'), $re_order_url)->toRenderable();
      $re_order_link['#attributes'] = [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode(['width' => 800]),
        'style' => 'margin-left: 16px;'
      ];

      $build['re_order'] = $re_order_link;

      $edit_url = Url::fromRoute('edit_guide_page.form', [], ['query' => ['guide_id' => $current_guide_id, 'current_node' => \Drupal::routeMatch()->getParameter('node')->id()]]);
      $edit_link = Link::fromTextAndUrl(t('Edit A Page'), $edit_url)->toRenderable();
      $edit_link['#attributes'] = [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode(['width' => 800]),
        'style' => 'margin-left: 16px;'
      ];

      $build['edit'] = $edit_link;

      $delete_url = Url::fromRoute('delete_guide_page.form', [], ['query' => ['guide_id' => $current_guide_id, 'current_node' => \Drupal::routeMatch()->getParameter('node')->id()]]);
      $delete_link = Link::fromTextAndUrl(t('Delete A Page'), $delete_url)->toRenderable();
      $delete_link['#attributes'] = [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode(['width' => 800]),
        'style' => 'margin-left: 16px; margin-bottom: 5px'
      ];

      $build['delete'] = $delete_link;
    }


    // Attach libraries necessary for modal functionality.
    $build['#attached']['library'][] = 'core/drupal.dialog.ajax';


    return $build;
  }

  public function getCurrentGuideId()
  {
    $current_node = \Drupal::routeMatch()->getParameter('node');

    if ($current_node->getType() == 'guide') {
      return $current_node->id();
    }
    elseif ($current_node->getType() == 'guide_page') {
      $parent = $current_node->get('field_parent_guide')->entity;

      if($parent->getType() == 'guide_page')
        $parent = $parent->get('field_parent_guide')->entity;

      return $parent->id();
    }

    return NULL;
  }
  public function getCacheMaxAge() {
    // Disable caching for this block.
    return 0;
  }
}


