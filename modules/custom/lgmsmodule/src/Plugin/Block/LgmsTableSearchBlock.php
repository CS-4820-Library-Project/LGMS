<?php

namespace Drupal\lgmsmodule\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Search Bar' Block.
 *
 * @Block(
 *   id = "lgms_all_guides_search_block",
 *   admin_label = @Translation("LGMS All Guides Search Bar"),
 *   category = @Translation("LGMS"),
 * )
 */
class LgmsTableSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build['#attached']['library'][] = 'lgmsmodule/lgmsmodule';

    $build['search'] = [
      '#type' => 'search',
      '#attributes' => ['class' => ['lgms-search lgms-all_guides-search'], 'placeholder' => $this->t('Search ...'),],
    ];

    return $build;
  }
}
