<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a controller for the LGMS Module.
 *
 * This controller is responsible for returning responses for specific routes defined
 * in the LGMS module. Primarily, it handles the display of all guides by rendering
 * a view and providing additional functionality based on user authentication.
 */
class AllGuidesController extends ControllerBase
{

  /**
   * Renders a list of all guides using a Drupal view and adds a search bar and
   * dashboard link for authenticated users.
   *
   * The method constructs a render array with the following:
   * - A Drupal view displaying all guides.
   * - A search bar that is shown to all users.
   * - A "My Dashboard" link that is only shown to authenticated users.
   *
   * It leverages the LGMS module's library for styling and utilizes the Views module
   * to fetch and display the guides. Permissions are checked to conditionally display
   * contextual links for users with the 'administer views' permission and to differentiate
   * the content for authenticated versus anonymous users.
   *
   * @return array
   *   A render array for Drupal to render the all guides page. It includes the view
   *   for all guides, possibly a search bar, and a dashboard link for authenticated users.
   */
  public function allGuides(): array
  {
    $build = [];

    // Helper object for performing database operations and fetching search bar.
    $landingMethods = new helperFunction(\Drupal::database());

    // Attempt to load the 'lgms_all_guides_table' view.
    $view = Views::getView('lgms_all_guides_table');

    // Attach the LGMS module's library for front-end styling.
    $build['#attached']['library'][] = 'lgmsmodule/lgmsmodule';

    if (is_object($view)) {
      // Set the display id
      $view->setDisplay('default');

      // Get the title from the view
      $title = $view->getTitle();

      // Render the view
      $rendered_view = $view->buildRenderable('default', []);

      // Add contextual links if the user has the permission to edit the view
      if (\Drupal::currentUser()->hasPermission('administer views')) {
        $rendered_view['#contextual_links']['views'] = [
          'route_parameters' => ['view' => 'lgms_all_guides_table', 'display_id' => 'default'],
        ];
      }

      if (\Drupal::currentUser()->isAuthenticated()) {
      // Add a container
        $build['top_row'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['lgms_all_guides_search_button_container']],
        ];

        // Render the searchbar block
        $build['top_row']['searchbar'] =  $landingMethods->getLGMSSearchBar('lgms_search_block', 'all_guides');

        // Render the dashboard button
        $build['top_row']['buttonDev'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['buttonDev']],
        ];


        // Add the button only for authenticated users.
        $build['top_row']['buttonDev'] = [
          '#type' => 'link',
          '#title' => 'My Dashboard',
          '#url' => Url::fromUri('internal:/lgms/dashboard'),
          '#attributes' => ['class' => ['button']],
        ];
      }
      else{
        // Render the searchbar block
        $build['searchbar'] =  $landingMethods->getLGMSSearchBar('lgms_search_block', 'dashboard');
      }

      // Include the rendered view in the build array.
      $build['table'] = [
        'view' => $rendered_view,
      ];
    }

    return $build;
  }
}
