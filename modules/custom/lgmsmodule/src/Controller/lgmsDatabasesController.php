<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\views\Views;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 * Handles the display of databases within the LGMS module.
 *
 * This controller is responsible for rendering a list of database entries
 * as defined in a View. It includes functionality for displaying the list,
 * showing a message when no entries are present, and adding contextual links
 * for users with appropriate permissions.
 */
class lgmsDatabasesController extends ControllerBase {

  /**
   * Renders the databases overview page.
   *
   * Prepares and returns a render array for displaying the databases. It attempts
   * to load and render a View named 'lgms_databases'. If the View has no results,
   * a message indicating the absence of database entries is displayed. Additionally,
   * a search bar and contextual links for editing the View are included for users
   * with the necessary permissions.
   *
   * @return array
   *   A Drupal render array containing the databases page content. This includes
   *   the databases list view, a search bar, and possibly a message indicating
   *   no database entries are available.
   */
  public function databases(): array
  {
    $build = [];

    // Attach the module's library for styling.
    $build['#attached']['library'][] = 'lgmsmodule/lgmsmodule';

    // Utilize helper functions for database operations.
    $landingMethods = new helperFunction(\Drupal::database());

    // Attempt to load the 'lgms_databases' view.
    $view = Views::getView('lgms_databases');

    if (is_object($view)) {
      $view->setDisplay('default');
      $view->execute();

      if (!$view->result) {
        // Display a message if the View has no results.
        $build['no_results'] = [
          '#markup' => $this->t('There are no Databases yet.'),
        ];
      } else {
        // Render the view and add it to the build array.
        $rendered_view = $view->buildRenderable('default', []);

        // Include contextual links for users with 'administer views' permission.
        if (\Drupal::currentUser()->hasPermission('administer views')) {
          $rendered_view['#contextual_links']['views'] = [
            'route_parameters' => ['view' => 'lgms_databases', 'display_id' => 'default'],
          ];
        }

        // Render the search bar and include it in the build array.
        $build['searchbar'] = $landingMethods->getLGMSSearchBar('lgms_search_block', 'dashboard');

        $build['table'] = ['view' => $rendered_view];
      }
    }

    return $build;
  }
}
