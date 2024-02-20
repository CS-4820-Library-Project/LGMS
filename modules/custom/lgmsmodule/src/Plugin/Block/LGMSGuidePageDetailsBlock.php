<?php
namespace Drupal\lgmsmodule\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Guide Page Details' Block.
 *
 * @Block(
 *   id = "guide_page_details",
 *   admin_label = @Translation("Guide Page Details Block"),
 *   category = @Translation("LGMS")
 * )
 */
class LGMSGuidePageDetailsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $current_node = \Drupal::routeMatch()->getParameter('node');

    if ($current_node->getType() == 'guide_page') {

      $page_title = $current_node->get("field_page_name")->value;
      $build['#title'] = $page_title;
      $page_description = $current_node->get('field_page_description')->value;



      $build['page_description'] = [
        '#type' => 'markup',
        '#markup' => $page_description,

      ];


    }


    return $build;
  }
}
