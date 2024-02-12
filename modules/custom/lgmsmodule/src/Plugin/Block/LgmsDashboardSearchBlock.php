<?php

namespace Drupal\lgmsmodule\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Search Bar' Block.
 *
 * @Block(
 *   id = "lgms_dashboard_search_block",
 *   admin_label = @Translation("LGMS Dashboard Search Bar"),
 *   category = @Translation("LGMS"),
 * )
 */
class LgmsDashboardSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build['#attached']['library'][] = 'lgmsmodule/lgmsmodule';

    $build['search'] = [
      '#type' => 'search',
      '#attributes' => ['class' => ['lgms-search lgms-dashboard-search'], 'placeholder' => $this->t('Search by guide name...'),],
    ];

    return $build;
  }
}
