<?php

namespace Drupal\lgmsmodule\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Hello' Block.
 *
 * @Block(
 *   id = "hello_block",
 *   admin_label = @Translation("Hello block"),
 *   category = @Translation("Hello World"),
 * )
 */
class HelloBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build['#attached']['library'][] = 'lgmsmodule/lgmsmodule';

    $build['search'] = [
      '#type' => 'search',
      '#attributes' => ['class' => ['lgms-search'], 'placeholder' => $this->t('Search by guide name...'),],
    ];

    return $build;
  }

}
