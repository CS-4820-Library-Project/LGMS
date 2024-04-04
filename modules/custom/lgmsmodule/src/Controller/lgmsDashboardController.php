<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\views\Views;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 * Controller for the Dashboard page.
 */
class lgmsDashboardController extends ControllerBase
{

  /**
   * Displays the Dashboard page.
   *
   * @return array
   *   A render array containing the page content.
   */
  public function overview() {
    $build = [];
    $build['#attached']['library'][] = 'lgmsmodule/lgmsmodule';
    $landingMethods = new helperFunction(\Drupal::database());
    $view = Views::getView('lgms_dashboard_table');

    if (is_object($view)) {
      // Set the display id
      $view->setDisplay('default');

      // Get the title from the view
      $title = $view->getTitle();

      // Execute the view query to get the results
      $view->execute();

      // Check if the view has any results
      if (!$view->result) {
        $build['no_results'] = [
          '#markup' => $this->t('You don’t own any guides yet.'),
        ];
      } else {
        // Render the view
        $rendered_view = $view->buildRenderable('default', []);

        // Add contextual links if the user has the permission to edit the view
        if (\Drupal::currentUser()->hasPermission('administer views')) {
          $rendered_view['#contextual_links']['views'] = [
            'route_parameters' => ['view' => 'lgms_dashboard_table', 'display_id' => 'default'],
          ];
        }

        // Render the searchbar block
        $build['searchbar'] =  $landingMethods->getLGMSSearchBar('lgms_search_block', 'dashboard');

        // Add the title and the rendered view to the build array
        $build['table'] = [
          'view' => $rendered_view,
        ];
      }
    }

    return $build;
  }

  public function new()
  {
    // Generate the content for creating new items.
    // add a new guide.
    $node = Node::create(['type' => 'guide']);
    $form = $this->entityFormBuilder()->getForm($node);

    return $form;
    $build = [];
  }

  public function reuse()
  {
    // Load the custom form using the form builder service.
    $form = \Drupal::formBuilder()->getForm('Drupal\lgmsmodule\Form\ReuseGuideForm');

    // Return the form render array.
    return $form;
  }

  //public function edit() {
  //  // Generate the content for editing items.

  //    $node = Node::create(['type' => 'guide']);
  //      $form = $this->entityFormBuilder()->getForm($node);

  //    return $form;
  //      $build = [];
  //}
}
