<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\lgmsmodule\sql\sqlMethods;

/**
 * Returns responses for LGMS Module routes.
 */
class LgmsModuleController extends ControllerBase
{

  /**
   * Returns the page content.
   */
  public function content($sort_by = NULL)
  {
    $sqlMethods = new sqlMethods(\Drupal::database());
    $landingMethods = new LandingPageHelper();
    $build = [];

    // Get the sorting parameter from the URL if it's not provided as a parameter.
    if ($sort_by === NULL) {
      $sort_by = \Drupal::request()->query->get('sort_by', 'guide_Name'); // Default to sorting by owner.
    }


    $headers = [
      ['data' => $this->t('Guide Name')],
      ['data' => $this->t('Owner')],
      ['data' => $this->t('Last Updated')],
    ];

    // Fetch titles of all "Article" nodes from the database.
    $result = $sqlMethods->getGuides();

    // Build table rows.
    $rows = [];
    foreach ($result as $nid => $record) {
      $name = $sqlMethods->getOwner($record->uid);

      $articleLink = $landingMethods->getLink($nid);

      $rows[] = [
        array('data' => new FormattableMarkup('<a href=":link">@name</a>',
          [':link' => $articleLink,
            '@name' => $record->title])),
        $name,
        date('Y-m-d', $record->changed),
      ];
    }


    // Add a sorting dropdown form above the table.
    $build['sorting_form'] = \Drupal::formBuilder()->getForm('Drupal\lgmsmodule\Form\SortingForm', $headers, $rows);


    return $build;
  }
}
