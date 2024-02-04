<?php

namespace Drupal\lgmsmodule\Controller;
use Drupal\views\Views;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the Dashboard page.
 */
class lgmsDashboardController extends ControllerBase {

  /**
   * Displays the Dashboard page.
   *
   * @return array
   *   A render array containing the page content.
   */
  public function dashboardPage() {
      $build = [];
      $view = Views::getView('lgmstable');

      if (is_object($view)) {
        // Set the display id
        $view->setDisplay('default');

        // Get the title from the view
        $title = $view->getTitle();

        // Render the view
        $rendered_view = $view->render();

        // Add the title and the rendered view to the build array
        $build['table'] = [
          //'title' => [
          //'#markup' => '<h2>' . $title . '</h2>',
          //],
          'view' => $rendered_view,
        ];
      }

      return $build;
    }
}
