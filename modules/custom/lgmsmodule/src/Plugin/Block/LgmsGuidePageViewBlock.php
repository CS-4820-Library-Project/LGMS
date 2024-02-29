<?php

namespace Drupal\lgmsmodule\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\lgmsmodule\sql\sqlMethods;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Serialization\Json;

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

    $build['#title'] = $guide_title;

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

        // Only show the link to add a sub-page if one doesn't already exist.
        if (!$sub_page_exists) {
          // Create the URL for adding a new sub-page.
          $add_sub_page_url = \Drupal\Core\Url::fromRoute('node.add', ['node_type' => 'sub_page'], [
            'query' => ['field_parent_page' => $page->nid], // Adjust the field name if needed.
          ]);
          // Create the link for adding a new sub-page.
          $add_sub_page_link = \Drupal\Core\Link::fromTextAndUrl(t('Add new sub page +'), $add_sub_page_url)->toString();

          $page_item['add_sub_page'] = [
            '#markup' => $add_sub_page_link,
            '#prefix' => '<div class="add-sub-page-link">',
            '#suffix' => '</div>',
          ];
        }
      }


      // Add the page item to the guide list.
      $build['guide_container']['content']['#items'][] = $page_item;

    }

    if (\Drupal::currentUser()->hasPermission('create guide page') && $current_guide_id != null) {
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

      $sqlMethods = new sqlMethods(\Drupal::database());
      return $sqlMethods->getGuideNodeIdByPageId($current_node->id());

    }
    return NULL;
  }
  public function getCacheMaxAge() {
    // Disable caching for this block.
    return 0;
  }
}


