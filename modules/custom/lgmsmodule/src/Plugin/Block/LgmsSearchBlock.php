<?php

namespace Drupal\lgmsmodule\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Search Bar' Block.
 *
 * @Block(
 *   id = "lgms_search_block",
 *   admin_label = @Translation("LGMS Search Bar"),
 *   category = @Translation("LGMS"),
 * )
 */
class LgmsSearchBlock extends BlockBase {
  private string $type;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build['#attached']['library'][] = 'lgmsmodule/lgmsmodule';

    $classes = 'lgms-search ';

    if($this->type == "dashboard"){
      $classes .= 'lgms-dashboard-search';
    }
    else {
      $classes .= 'lgms-all_guides-search';
    }

    $build['search'] = [
      '#type' => 'search',
      '#attributes' => ['class' => [$classes], 'placeholder' => $this->t('Search ...'),],
    ];

    return $build;
  }

  public function setType($type) {
    $this->type = $type;
  }
}
