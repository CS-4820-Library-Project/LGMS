<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\views\Views;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 * Controller for the Dashboard page.
 */
class lgmsDatabasesController extends ControllerBase
{

  /**
   * Displays the Dashboard page.
   *
   * @return array
   *   A render array containing the page content.
   */
  public function databases() {
    $build = [];
    $build['#attached']['library'][] = 'lgmsmodule/lgmsmodule';
    $landingMethods = new landingPageHelper();
    $view = Views::getView('lgms_databases');

    if (is_object($view)) {
      // Set the display id
      $view->setDisplay('default');

      // Execute the view query to get the results
      $view->execute();

      // Check if the view has any results
      if (!$view->result) {
        $build['no_results'] = [
          '#markup' => $this->t('There is not any Databases yet.'),
        ];
      } else {
        // Render the view
        $rendered_view = $view->buildRenderable('default', []);

        // Add contextual links if the user has the permission to edit the view
        if (\Drupal::currentUser()->hasPermission('administer views')) {
          $rendered_view['#contextual_links']['views'] = [
            'route_parameters' => ['view' => 'lgms_databases', 'display_id' => 'default'],
          ];
        }

        // Render the searchbar block
        $build['searchbar'] =  $landingMethods->getLGMSSearchBar('lgms_dashboard_search_block');

        // Add the title and the rendered view to the build array
        $build['table'] = [
          'view' => $rendered_view,
        ];
      }
    }

    return $build;
  }
}