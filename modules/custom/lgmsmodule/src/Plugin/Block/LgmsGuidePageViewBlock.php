<?php

namespace Drupal\lgmsmodule\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\lgmsmodule\sql\sqlMethods;
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
    $sqlMethods = new sqlMethods(\Drupal::database());


    $current_guide_id = $this->getCurrentGuideId();
    $current_guide = Node::load($current_guide_id);

    $guide_title = '';
    if ($current_guide_id) {
      $guide_title = $current_guide->label();
    }

    $current_guide_url = $current_guide->toUrl()->toString();
    $current_guide_url = str_ireplace('LGMS/', '', $current_guide_url);

    $url = Url::fromUri('internal:' . $current_guide_url);
    $link = Link::fromTextAndUrl($guide_title, $url)->toRenderable();

    $build['#title'] = $link;

    // Add a list to your block.
    $build['guide_container']['content'] = [
      '#theme' => 'item_list',
      '#items' => [],
    ];

    $pages = $current_guide->get('field_child_pages')->referencedEntities();

    foreach ($pages as $page) {
      $class = '';
      if($page->isPublished() == 0){
        if(!\Drupal::currentUser()->isAuthenticated()){
          continue;
        }
        $class = 'node--unpublished';
      }

      $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $page->id()]);
      $link = \Drupal\Core\Link::fromTextAndUrl($page->label(), $url)->toString();

      $page_item['page'] = [
        '#markup' => '<div class="' . $class . '">' . $link . '</div>', // Wrap in div for styling purposes.
        'sub_pages' => [
          '#theme' => 'item_list',
          '#items' => [],
        ],
      ];

      // Retrieve sub-pages for the current page.
      $sub_pages = $page->get('field_child_pages')->referencedEntities();

      // Add sub-pages to the list if they exist.
      if (!empty($sub_pages) && $page->get('field_parent_guide')->entity->id() == $current_guide->id()) {
        foreach ($sub_pages as $sub_page) {
          $sub_page_class = '';
          if($sub_page->isPublished() == 0){
            if(!\Drupal::currentUser()->isAuthenticated()){
              continue;
            }
            $sub_page_class = 'node--unpublished';
          }

          $sub_url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $sub_page->id()]);
          $sub_link = \Drupal\Core\Link::fromTextAndUrl($sub_page->label(), $sub_url)->toString();
          $page_item['sub_pages']['#items'][] = [
            '#markup' => '<div class="' . $sub_page_class . ' sub-page-item">' . $sub_link . '</div>',
          ];
        }
      }

      // Add the page item to the guide list.
      $build['guide_container']['content']['#items'][] = $page_item;
      $page_item = [];
    }

    if (\Drupal::currentUser()->hasPermission('create guide_page content') && $current_guide_id != null) {
      // Generate the URL for the custom form route, including the query parameter for the current guide.
      $array_of_objects = [(object)['name' => 'Create Guide Page', 'form' => 'CreateGuidePageForm'],(object) ['name' => 'Reuse Guide Page', 'form' => 'ReuseGuidePageForm']];
      $json_data = json_encode($array_of_objects);
      $query_param = urlencode($json_data);

      $ids = ['current_guide_id' => $current_guide_id];
      $json_data = json_encode($ids);
      $ids = urlencode($json_data);

      $url = Url::fromRoute('lgmsmodule.popup_modal', [], ['query' => ['ids' => $ids, 'forms' => $query_param]]);

      // Create the link render array with AJAX attributes.
      $link = Link::fromTextAndUrl(t('Create/Reuse Guide Page'), $url)->toRenderable();
      $link['#attributes'] = [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode(['width' => 800]),
      ];

      // Render the link somewhere in your build array.
      $build['guide_page_modal'] = $link;

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


