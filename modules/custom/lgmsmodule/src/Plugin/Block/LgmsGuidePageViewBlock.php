<?php

namespace Drupal\lgmsmodule\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\lgmsmodule\sql\sqlMethods;

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


      $build['guide_container']['content']['#items'][] = [
        '#markup' => $link,
      ];
    }
    if (\Drupal::currentUser()->hasPermission('create guide page')  && $current_guide_id!= null) {

      $add_page_url = \Drupal\Core\Url::fromRoute('node.add', ['node_type' => 'guide_page'], [
        'query' => ['field_guide_reference' => $current_guide_id],
      ]);
      $add_page_link = \Drupal\Core\Link::fromTextAndUrl(t('Add new page +'), $add_page_url)->toString();

      $build['guide_container']['content']['#items'][] = [
        '#markup' => $add_page_link,
      ];
    }
    $build['#attached']['library'][] = 'lgmsmodule/lgmsmodule';
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
}
