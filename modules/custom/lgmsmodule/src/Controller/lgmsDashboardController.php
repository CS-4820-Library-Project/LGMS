<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\views\Views;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 * Manages the display and functionality of the LGMS Dashboard.
 *
 * This controller provides methods for rendering the dashboard overview,
 * adding new guides, and reusing existing guides through a custom form. It utilizes
 * Drupal's View system to list items on the dashboard and leverages Drupal's Form API
 * for the creation and management of content.
 */
class lgmsDashboardController extends ControllerBase {

  /**
   * Renders the LGMS Dashboard overview page.
   *
   * This method prepares and returns a render array for the dashboard page,
   * which includes a list of guides displayed through a View. If no guides are
   * available, a message is displayed. It also includes a search bar and
   * contextual links for users with appropriate permissions.
   *
   * @return array
   *   A Drupal render array containing the dashboard content, including the
   *   guides list view, a search bar, and potentially a message indicating
   *   no guides are owned by the user.
   */
  public function overview(): array
  {
    $landingMethods = new helperFunction(\Drupal::database());
    $build = [];

    $build['#attached']['library'][] = 'lgmsmodule/lgmsmodule';

    $view = Views::getView('lgms_dashboard_table');

    if (is_object($view)) {
      $view->setDisplay('default');
      $view->execute();

      if (!$view->result) {
        $build['no_results'] = [
          '#markup' => $this->t('You don’t own any guides yet.'),
        ];
      } else {
        $rendered_view = $view->buildRenderable('default', []);
        if (\Drupal::currentUser()->hasPermission('administer views')) {
          $rendered_view['#contextual_links']['views'] = [
            'route_parameters' => ['view' => 'lgms_dashboard_table', 'display_id' => 'default'],
          ];
        }

        $build['searchbar'] = $landingMethods->getLGMSSearchBar('lgms_search_block', 'dashboard');
        $build['table'] = ['view' => $rendered_view];
      }
    }

    return $build;
  }

  /**
   * Provides a form for creating a new guide.
   *
   * This method utilizes Drupal's entity form system to generate a form for
   * creating a new guide of the 'guide' content type. It returns the form
   * render array to Drupal for display.
   *
   * @return array
   *   The form render array for creating a new guide.
   */
  public function new(): array
  {
    $node = Node::create(['type' => 'guide']);
    return $this->entityFormBuilder()->getForm($node);
  }

  /**
   * Renders the form for reusing existing guides.
   *
   * Fetches and returns the custom form defined by 'Drupal\lgmsmodule\Form\ReuseGuideForm'
   * allowing users to reuse existing guides. This demonstrates an example of
   * integrating custom forms within controller methods.
   *
   * @return array
   *   The form render array for the guide reuse form.
   */
  public function reuse(): array
  {
    return \Drupal::formBuilder()->getForm('Drupal\lgmsmodule\Form\ReuseGuideForm');
  }
}
