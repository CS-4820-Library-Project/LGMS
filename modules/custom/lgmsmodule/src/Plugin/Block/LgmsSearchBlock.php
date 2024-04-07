<?php

namespace Drupal\lgmsmodule\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block for a search bar specifically designed for LGMS.
 *
 * This block generates a search input field that can be styled and configured
 * differently based on the context it is used in. The context is determined by
 * the 'type' property, which adjusts the CSS class applied to the search input
 * for different styling opportunities.
 *
 * @Block(
 *   id = "lgms_search_block",
 *   admin_label = @Translation("LGMS Search Bar"),
 *   category = @Translation("LGMS"),
 * )
 */
class LgmsSearchBlock extends BlockBase {
  /**
   * The type of search bar, affecting its styling and placement.
   *
   * @var string
   */
  private string $type;

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * This method is responsible for generating the search bar's HTML structure,
   * along with any attached libraries or attributes. The appearance and behavior
   * of the search bar can be influenced by the 'type' property.
   *
   * @return array
   *   A renderable array representing the content of the block. This includes
   *   the search input field and any necessary libraries or attributes for
   *   functionality and styling.
   */
  public function build(): array
  {
    $build = [];
    $build['#attached']['library'][] = 'lgmsmodule/lgmsmodule';

    $build['search'] = [
      '#type' => 'search',
      '#attributes' => [
        'class' => [
          'lgms-search',
          $this->type == "dashboard"? 'lgms-dashboard-search' : 'lgms-all_guides-search'
        ],
        'placeholder' => $this->t('Search ...'),
      ],
    ];

    return $build;
  }

  /**
   * Sets the type of the search bar.
   *
   * The type influences the CSS classes applied to the search bar, allowing for
   * different styling based on where the search bar is used (e.g., dashboard or
   * guides list).
   *
   * @param string $type
   *   The type of the search bar, determining its styling context.
   */
  public function setType(string $type): void
  {
    $this->type = $type;
  }
}
