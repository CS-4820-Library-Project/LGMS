<?php

namespace Drupal\lgmsmodule\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Tango Guide' Block.
 *
 * @Block(
 *   id = "tango_guide_block",
 *   admin_label = @Translation("Tango Guide Block"),
 *   category = @Translation("Custom"),
 * )
 */
class TangoGuideBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'inline_template',
      '#template' => '<iframe src="{{ url }}" width="100%" height="600" style="border:none;"></iframe>',
      '#context' => ['url' => 'https://app.tango.us/app/embed/2433334b-218b-449e-ac47-cad62f893575'],
    ];
  }

}
