<?php

namespace Drupal\lgmsmodule\Controller;
use Drupal\views\Views;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

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
  public function overview() {
      $build = [];
      $view = Views::getView('lgms_dashboard_table');

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

     public function new() {
    // Generate the content for creating new items.
      $node = Node::create(['type' => 'guide']);
        $form = $this->entityFormBuilder()->getForm($node);

      return $form;
        $build = [];

      }

  public function import() {
    // Generate the content for importing items.
      $node = Node::create(['type' => 'guide']);
        $form = $this->entityFormBuilder()->getForm($node);

      return $form;
        $build = [];
  }

  public function edit() {
    // Generate the content for editing items.

      $node = Node::create(['type' => 'guide']);
        $form = $this->entityFormBuilder()->getForm($node);

      return $form;
        $build = [];
  }
}