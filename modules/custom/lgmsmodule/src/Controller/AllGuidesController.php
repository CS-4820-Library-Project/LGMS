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
    $landingMethods = new landingPageHelper();
    $view = Views::getView('lgms_all_guides_table');
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

      // Add a container
      $build['top_row'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['lgms_all_guides_search_button_container']],
      ];

      // Render the searchbar block
      $build['top_row']['searchbar'] =  $landingMethods->getLGMSSearchBar('lgms_all_guides_search_block');

      // Render the dashboard button
      $build['top_row']['buttonDev'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['buttonDev']],
      ];

      $build['top_row']['buttonDev']['link'] = [
        '#title' => 'My Dashboard',
        '#type' => 'link',
        '#url' => Url::fromUri('internal:/lgms/dashboard'),
        '#attributes' => ['class' => ['button']],
      ];

      $build['table'] = [
        'view' => $rendered_view,
      ];
    }

    return $build;
  }
}
