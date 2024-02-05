<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\lgmsmodule\sql\sqlMethods;

/**
 * Returns responses for LGMS Module routes.
 */
class AllGuidesController extends ControllerBase
{

  /**
   * Returns the page content.
   */
  public function allGuides(): array
  {
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
