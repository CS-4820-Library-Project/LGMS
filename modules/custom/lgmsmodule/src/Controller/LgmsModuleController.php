<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for LGMS Module routes.
 */
class LgmsModuleController extends ControllerBase
{


  public function byOwner()
  {
    $build = [];

    // Your existing breadcrumb and setup code here
    $build['breadcrumb'] = [
      '#markup' => $this->generateBreadcrumbs(),
    ];

    $mock_data = $this->getMockData(); // This method should return your mock data array

    // Extract unique owners
    $owners = array_unique(array_column($mock_data, 0)); // Assuming the 1st element is the owner

    // Sort owners alphabetically
    sort($owners);

    // Build a renderable list of owners
    $build['owners'] = [
      '#theme' => 'item_list',
      '#items' => $owners,
      '#title' => $this->t('Owners'),
    ];

    return $build;
  }

  public function byType()
  {
    $build = [];

    $build['breadcrumb'] = [
      '#markup' => $this->generateBreadcrumbs(),
    ];

    $mock_data = $this->getMockData();

    // Process data to get unique types
    $types = array_unique(array_column($mock_data, 4));

    // Build a table for types
    $build['types'] = [
      '#type' => 'table',
      '#header' => [$this->t('Type')],
      '#rows' => array_map(function ($type) {
        return [$type];
      }, $types),
      '#empty' => $this->t('No types available.'),
    ];

    return $build;
  }

  public function bySubject()
  {
    $build = [];

    // Your existing breadcrumb and setup code here
    $build['breadcrumb'] = [
      '#markup' => $this->generateBreadcrumbs(),
    ];

    $mock_data = $this->getMockData(); // This method should return your mock data array

    // Extract unique subjects
    $subjects = array_unique(array_column($mock_data, 3)); // Assuming the 4th element is the subject

    // Sort subjects alphabetically
    sort($subjects);

    // Build a renderable list of subjects
    $build['subjects'] = [
      '#theme' => 'item_list',
      '#items' => $subjects,
      '#title' => $this->t('Subjects'),
    ];

    return $build;
  }

  private function getMockData()
  {
    return [
      // ... Your mock data array ...
      ['Jayvion Simon', 'Getting Started narrowing a big core topic down', '2023-07-21', 'subject A', 'type A'],
      ['Deja Brady', 'Nikolaus - Leuschke', '2023-04-26', 'subject b', 'type b'],
      ['Harrison Stein', 'Gleichner, Mueller and Tromp', '2023-09-28', 'subject c', 'type c'],
      ['Lucian Obrien', 'Hegmann, Kreiger and Bayer', '2023-04-10', 'subject d', 'type d'],
      ['Reece Chung', 'Lueilwitz and Sons', '2023-05-12', 'subject e', 'type e'],
      ['Jayvion Simon', 'Getting Started narrowing a big core topic down', '2023-07-21', 'subject f', 'type f'],
      ['Deja Brady', 'Nikolaus - Leuschke', '2023-04-26', 'subject g', 'type g'],
      ['Harrison Stein', 'Gleichner, Mueller and Tromp', '2023-09-28', 'subject h', 'type h'],
      ['Lucian Obrien', 'Hegmann, Kreiger and Bayer', '2023-04-10', 'subject i', 'type i'],
      ['Reece Chung', 'Lueilwitz and Sons', '2023-05-12', 'subject j', 'type j'],
    ];
  }

  private function generateBreadcrumbs()
  {
    $breadcrumb = [];

    // Parse the current URL
    $current_path = parse_url('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $paths = explode('/', $current_path);

    // Generate breadcrumb links
    $current_url = 'http://' . $_SERVER['HTTP_HOST'];
    foreach ($paths as $path) {
      if (!empty($path)) {
        $current_url .= '/' . $path;
        $breadcrumb[] = '<a href="' . $current_url . '">' . ucfirst($path) . '</a>';
      }
    }

    return implode(' / ', $breadcrumb);
  }

  /**
   * Returns the page content.
   */
  public function content($sort_by = NULL)
  {

    $build = [];

    $build['breadcrumb'] = [
      '#markup' => $this->generateBreadcrumbs(),
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

    $mock_data = $this->getMockData();

    // Sort the data based on the selected sorting option.
    usort($mock_data, function ($a, $b) use ($sort_by) {
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


    return $build;
  }
}
