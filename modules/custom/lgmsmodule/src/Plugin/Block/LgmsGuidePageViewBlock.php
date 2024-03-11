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


    //$current_guide_id = 33;
    $current_guide_id = $this->getCurrentGuideId();

    // Get the pages for the current guide.
    $pages = $sqlMethods->getGuidePages($current_guide_id);

    $guide_title = '';
    if ($current_guide_id) {
      $guide_title = $sqlMethods->getTitle($current_guide_id);
    }

    // Check if the guide title was successfully retrieved.
    if (!$guide_title) {

      $guide_title = 'Guide';
    }

    $current_guide = Node::load($current_guide_id);
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

    foreach ($pages as $page) {
      $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $page->nid]);
      $link = \Drupal\Core\Link::fromTextAndUrl($page->title, $url)->toString();

      $page_item = [
        '#markup' => '<div>' . $link . '</div>', // Wrap in div for styling purposes.
        'sub_pages' => [
          '#theme' => 'item_list',
          '#items' => [],
        ],
      ];

      // Retrieve sub-pages for the current page.
      $sub_pages = $sqlMethods->getSubPages($page->nid);

      // Add sub-pages to the list if they exist.
      if (!empty($sub_pages)) {
        foreach ($sub_pages as $sub_page) {
          $sub_url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $sub_page->nid]);
          $sub_link = \Drupal\Core\Link::fromTextAndUrl($sub_page->title, $sub_url)->toString();
          $page_item['sub_pages']['#items'][] = [
            '#markup' => '<div class="sub-page-item">' . $sub_link . '</div>',
          ];
        }
      }

      // Check if the user has permission to create sub-pages and if no sub-page exists.
      if (\Drupal::currentUser()->hasPermission('create sub_page content ')) {
        // Check if a sub-page already exists for this page.
        $sub_page_exists = $sqlMethods->subPageExists($page->nid);
        $parent_page_id =$page->nid;

        // Only show the link to add a sub-page if one doesn't already exist.
        if (!$sub_page_exists) {
          // Create the URL for adding a new sub-page.
          $add_sub_page_url = Url::fromRoute('create_sub_page.form', [], [
            'query' => ['parent_page' => $parent_page_id]
          ]);

          $add_sub_page_link = Link::fromTextAndUrl(t('Add new sub page +'), $add_sub_page_url)->toRenderable();
          $add_sub_page_link['#attributes'] = [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode(['width' => 800]),
          ];

          // Append the 'Add new sub page' link render array to the sub-pages list.
          $page_item['add_sub_pages']['#items'][] = $add_sub_page_link;

          $add_sub_page_url = Url::fromRoute('reuse_sub_page.form', [], [
            'query' => ['parent_page' => $parent_page_id]
          ]);

          $add_sub_page_link = Link::fromTextAndUrl(t('Reuse sub page +'), $add_sub_page_url)->toRenderable();
          $add_sub_page_link['#attributes'] = [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode(['width' => 800]),
          ];

          // Append the 'Add new sub page' link render array to the sub-pages list.
          $page_item['reuse_sub_pages']['#items'][] = $add_sub_page_link;
        }
      }


      // Add the page item to the guide list.
      $build['guide_container']['content']['#items'][] = $page_item;

    }

    if (\Drupal::currentUser()->hasPermission('create guide_page content') && $current_guide_id != null) {
      // Generate the URL for the custom form route, including the query parameter for the current guide.
      $url = Url::fromRoute('add_guide_page.form', [], ['query' => ['current_guide' => $current_guide_id]]);

      // Create the link render array with AJAX attributes.
      $link = Link::fromTextAndUrl(t('Add Guide Page'), $url)->toRenderable();
      $link['#attributes'] = [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode(['width' => 800]),
      ];

      // Render the link somewhere in your build array.
      $build['add_guide_page_link'] = $link;

      // Attach the library necessary for using the modal dialog
      // $build['#attached']['library'][] = 'core/drupal.dialog.ajax';

      // Generate the URL for the custom form route, including the query parameter for the current guide.
      $url = Url::fromRoute('import_guide_page.form', [], ['query' => ['current_guide' => $current_guide_id]]);

      // Create the link render array with AJAX attributes.
      $link = Link::fromTextAndUrl(t('Import Guide Page'), $url)->toRenderable();
      $link['#attributes'] = [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode(['width' => 800]),
      ];

      // Render the link somewhere in your build array.
      $build['import_guide_page_link'] = $link;
    }


// Attach libraries necessary for modal functionality.
    $build['#attached']['library'][] = 'core/drupal.dialog.ajax';


    return $build;
  }

  public function getCurrentGuideId()
  {
    $sqlMethods = new sqlMethods(\Drupal::database());
    $current_node = \Drupal::routeMatch()->getParameter('node');
    if ($current_node instanceof \Drupal\node\NodeInterface) {
      // If it's a guide, return its ID.
      if ($current_node->bundle() == 'guide') {
        return $current_node->id();
      }
      // If it's a guide page, get and return the parent guide ID.
      elseif ($current_node->bundle() == 'guide_page') {
        return $sqlMethods->getGuideNodeIdByPageId($current_node->id());
      }
      // If it's a sub-page, first get the parent page ID, then get and return the parent guide ID.
      elseif ($current_node->bundle() == 'sub_page') {
        $parent_page_id = $sqlMethods->getPageNodeIdBySubPageId($current_node->id());
        return $parent_page_id ? $sqlMethods->getGuideNodeIdByPageId($parent_page_id) : NULL;
      }
    }
    return NULL;
  }
  public function getCacheMaxAge() {
    // Disable caching for this block.
    return 0;
  }
}


