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

    $mock_data = $this->getMockData();

    // Group data by owners
    $data_by_owners = [];
    foreach ($mock_data as $data) {
      $owner = $data[0]; // Assuming the 1st element is the owner
      $data_by_owners[$owner][] = $data[1]; // Assuming the 2nd element is the detail you want to list
    }

    // Create accordion items
    $accordion_items = [];
    foreach ($data_by_owners as $owner => $details) {
      $accordion_items[] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $owner,
        '#children' => [
          '#theme' => 'item_list',
          '#items' => $details,
        ],
      ];
    }

    $build['owners_accordion'] = $accordion_items;

    return $build;
  }


  public function byType()
  {
    $build = [];

    $mock_data = $this->getMockData();

    // Group data by types
    $data_by_types = [];
    foreach ($mock_data as $data) {
      $type = $data[4]; // Assuming the 5th element is the type
      $data_by_types[$type][] = $data[1]; // Assuming the 2nd element is the detail you want to list
    }

    // Create accordion items
    $accordion_items = [];
    foreach ($data_by_types as $type => $details) {
      $accordion_items[] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $type,
        '#children' => [
          '#theme' => 'item_list',
          '#items' => $details,
        ],
      ];
    }

    $build['types_accordion'] = $accordion_items;

    return $build;
  }


  public function bySubject()
  {
    $build = [];

    $mock_data = $this->getMockSubjectGuideData(); // This method should return your mock data array

    // Extract unique subjects
    foreach ($mock_data as $data) {
      $subject = $data['subject']; // Assuming subject is a key in your data array
      $guides = $data['guides'];   // Assuming guides is an array of guide names


      // Build the accordion item
      $accordion_items[] = array(
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $subject,
        '#attributes' => array('class' => array('accordion-item')),
        '#children' => array(
          '#theme' => 'item_list',
          '#items' => $guides,
        ),
      );
    }

    $build = array(
      'accordion' => $accordion_items,
    );

    return $build;
  }

  function getMockSubjectGuideData() {
    return array(
      array(
        'subject' => 'Mathematics',
        'guides' => array(
          'Algebra',
          'Geometry',
          'Trigonometry',
          'Calculus',
          'Statistics',
        ),
      ),
      array(
        'subject' => 'Science',
        'guides' => array(
          'Biology',
          'Chemistry',
          'Physics',
          'Geology',
          'Astronomy',
        ),
      ),
      array(
        'subject' => 'History',
        'guides' => array(
          'Ancient Civilizations',
          'World Wars',
          'Modern History',
          'Renaissance',
          'Industrial Revolution',
        ),
      ),
      array(
        'subject' => 'Literature',
        'guides' => array(
          'Shakespeare',
          'Victorian Literature',
          'American Literature',
          'Modern Fiction',
          'Poetry',
        ),
      ),
      array(
        'subject' => 'Computer Science',
        'guides' => array(
          'Algorithms',
          'Data Structures',
          'Database Management',
          'Web Development',
          'Artificial Intelligence',
        ),
      ),
      array(
        'subject' => 'Art',
        'guides' => array(
          'Painting',
          'Sculpture',
          'Photography',
          'Architecture',
          'Film',
        ),
      ),
      array(
        'subject' => 'Music',
        'guides' => array(
          'Music Theory',
          'Classical Music',
          'Jazz',
          'Rock',
          'Electronic Music',
        ),
      ),
      array(
        'subject' => 'Languages',
        'guides' => array(
          'English',
          'Spanish',
          'French',
          'German',
          'Mandarin',
        ),
      ),
      array(
        'subject' => 'Psychology',
        'guides' => array(
          'Behavioral Psychology',
          'Cognitive Psychology',
          'Developmental Psychology',
          'Social Psychology',
          'Clinical Psychology',
        ),
      ),
      array(
        'subject' => 'Business',
        'guides' => array(
          'Marketing',
          'Finance',
          'Management',
          'Entrepreneurship',
          'Accounting',
        ),
      ),
    );
  }

  private function getMockData()
  {
    return [
      ['Guide to Ancient Greek Literature', 'Dr. Helena Markos', '2023-01-15', 'Literature', 'Reference'],
      ['Understanding Quantum Mechanics', 'Prof. Albert Newman', '2022-11-20', 'Physics', 'Tutorial'],
      ['Renaissance Art Techniques', 'Maria Vasquez', '2023-02-09', 'Art', 'Case Study'],
      ['Modern Web Development Practices', 'James Lee', '2023-01-05', 'Computer Science', 'Tutorial'],
      ['Introduction to Behavioral Psychology', 'Emma Clarkson', '2022-12-15', 'Psychology', 'Reference'],
      ['Astronomy for Beginners', 'Neil Burton', '2023-01-25', 'Astronomy', 'Tutorial'],
      ['History of the Industrial Revolution', 'Prof. Samuel Johnson', '2022-10-30', 'History', 'Reference'],
      ['Basics of Organic Chemistry', 'Lisa Young', '2023-03-05', 'Chemistry', 'Tutorial'],
      ['Guide to Baroque Music', 'Alexander G. Bell', '2022-09-15', 'Music', 'Case Study'],
      ['Fundamentals of Calculus', 'Dr. Emily White', '2023-01-18', 'Mathematics', 'Reference'],
      // ... more items ...
    ];
  }



  /**
   * Returns the page content.
   */
  public function content($sort_by = NULL)
  {

    $build = [];

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
