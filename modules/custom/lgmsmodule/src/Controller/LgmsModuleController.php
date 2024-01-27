<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

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

    // SQL

//    // Fetch titles of all "Article" nodes from the database.
//    $query = $this->database->select('node_field_data', 'n')
//      ->condition('n.type', 'article')
//      ->fields('n', ['title', 'uid', 'nid', 'changed']);
//    $result = $query->execute()->fetchAllAssoc('nid');
//    $current_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];      // Build table rows.
//    $rows = [];
//    foreach ($result as $nid => $record) {
//      $NameQuery = $this->database->select('users_field_data', 'u')
//        ->fields('u', ['name'])
//        ->condition('u.uid', $record->uid);
//      $NameQueryResult = $NameQuery->execute()->fetchField();
//      $rows[] = [$record->title, $NameQueryResult, date('Y-m-d', $record->changed)];
//    }
//    // Build the table.
//    $build['table'] = [
//      '#theme' => 'table',
//      '#header' => ['Title', 'owner', 'last modified'],
//      '#rows' => $rows,
//      ];

    $build = [];

    // Parse the current URL
    $current_path = parse_url('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $paths = explode('/', $current_path);
    $breadcrumb = [];

    // Generate breadcrumb links
    $current_url = 'http://' . $_SERVER['HTTP_HOST'];
    foreach ($paths as $path) {
      if (!empty($path)) {
        $current_url .= '/' . $path;
        $breadcrumb[] = '<a href="' . $current_url . '">' . ucfirst($path) . '</a>';
      }
    }

    //breadCrumbs
    $build['breadcrumb'] = [
      '#markup' => implode(' / ', $breadcrumb),
    ];

    // Get the sorting parameter from the URL if it's not provided as a parameter.
    if ($sort_by === NULL) {
      $sort_by = \Drupal::request()->query->get('sort_by', 'owner'); // Default to sorting by owner.
    }

    // Add a sorting dropdown form above the table.
    $build['sorting_form'] = \Drupal::formBuilder()->getForm('Drupal\lgmsmodule\Form\SortingForm');

    $header = [
      'owner' => $this->t('Owner'),
      'guide_name' => $this->t('Guide Name'),
      'last_updated' => $this->t('Last Updated'),
    ];

    $base_avatar_path = 'public://avatar/';

    $mock_data = [
      ['Jayvion Simon', 'Getting Started narrowing a big core topic down', '2023-07-21'],
      ['Deja Brady', 'Nikolaus - Leuschke', '2023-04-26'],
      ['Harrison Stein', 'Gleichner, Mueller and Tromp', '2023-09-28'],
      ['Lucian Obrien', 'Hegmann, Kreiger and Bayer', '2023-04-10'],
      ['Reece Chung', 'Lueilwitz and Sons', '2023-05-12'],
      ['Jayvion Simon', 'Getting Started narrowing a big core topic down', '2023-07-21'],
      ['Deja Brady', 'Nikolaus - Leuschke', '2023-04-26'],
      ['Harrison Stein', 'Gleichner, Mueller and Tromp', '2023-09-28'],
      ['Lucian Obrien', 'Hegmann, Kreiger and Bayer', '2023-04-10'],
      ['Reece Chung', 'Lueilwitz and Sons', '2023-05-12'],
    ];

    // Sort the data based on the selected sorting option.
    usort($mock_data, function($a, $b) use ($sort_by) {
      switch ($sort_by) {
        case 'owner':
          return strcmp($a[0], $b[0]);
        case 'guide_name':
          return strcmp($a[1], $b[1]);
        case 'last_updated':
          return strcmp($a[2], $b[2]);
      }
    });

    $rows = [];
    foreach ($mock_data as $data) {
      $rows[] = $data; // Simply pass each sub-array of mock data as a row
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No entries available.'),
      '#prefix' => '<div id="table-wrapper">',
      '#suffix' => '</div>',
    ];

    return $build;
  }

}
