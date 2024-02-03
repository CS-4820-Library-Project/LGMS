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
    //This is just an example of how blocks are created
    return [
      '#markup' => $this->t('Hello, World!'),
    ];
  }

}
