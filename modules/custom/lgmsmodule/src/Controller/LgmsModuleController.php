<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Url;
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
    $landingMethods = new landingPageHelper();
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

  /**
   * Returns a table of all Guides.
   */
  /**
   * Returns a table of all Guides.
   */
  public function allGuides() {
    $headers = [
      $this->t('Guide'),
      $this->t('Author'),
      $this->t('Subject'),
      $this->t('Type'),
      $this->t('Last Updated'),
    ];

    // Load guides.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'guide')
      ->sort('title', 'ASC')
      ->accessCheck(TRUE)
      ->execute();

    $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
    $rows = [];
    foreach ($nodes as $node) {
      // Get the guide title with a link.
      $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()]);
      $link = Link::fromTextAndUrl($node->label(), $url)->toString();

      // Get the author's name.
      $author = $node->getOwner();
      $author_name = $author ? $author->getDisplayName() : $this->t('Unknown');

      // Get the subject and type.
      $subject = $node->get('field_guide_subject')->entity;
      $type = $node->get('field_guide_type')->entity;
      $subject_name = $subject ? $subject->getName() : $this->t('N/A');
      $type_name = $type ? $type->getName() : $this->t('N/A');

      // Format the last updated time.
      $last_updated = \Drupal::service('date.formatter')->format($node->getChangedTime(), 'custom', 'm/d/Y');

      $rows[] = [
        'data' => [
          $link,
          $author_name,
          $subject_name,
          $type_name,
          $last_updated,
        ],
      ];
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No guides found'),
    ];

    return $build;
  }


}
