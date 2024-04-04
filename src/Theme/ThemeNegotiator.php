<?php

namespace Drupal\lgmsmodule\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\node\Entity\Node;

/**
 * Provides a theme negotiator that switches the theme for guide nodes.
 */
class ThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // Get the node ID from the route parameters.
    $node_id = $route_match->getRawParameter('node');

    if($node_id) {
      // Load the node.
      $node = Node::load($node_id);

      if ($node->getType() == 'guide') {

        $route_name = $route_match->getRouteName();
        $routes = [
          'entity.node.edit_form',
          'entity.node.delete_form',
          'entity.node.version_history',
          'entity.node.devel_load',
          'entity.node.devel_load_with_references',
          'entity.node.devel_render',
          'entity.node.devel_definition',
        ];

        // Check if the node exists and if its type is "guide".
        if (in_array($route_name, $routes)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    // Return the name of the theme to be used for guide nodes.
    return \Drupal::config('system.theme')->get('default');
  }

}
