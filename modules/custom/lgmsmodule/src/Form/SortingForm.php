<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SortingForm extends FormBase
{

  public function getFormId()
  {
    return 'lgmsmodule_sorting_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {

    $form['#attached']['library'][] = 'lgmsmodule/lgmsmodule';

    // Wrap the search and sort elements in a flex container.
    $form['search_sort_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['search-sort-container']],
    ];

    // Add the search input inside the container.
    $form['search_sort_container']['search'] = [
      '#type' => 'search',
      '#attributes' => [
        'class' => ['lgms-search'],
        'placeholder' => $this->t('Search by guide name...'),
      ],
    ];

    // Add the sorting select inside the container.
    $form['search_sort_container']['sort_by'] = [
      '#type' => 'select',
      '#options' => [
        'owner' => $this->t('Owner'),
        'guide_name' => $this->t('Guide Name'),
        'last_updated' => $this->t('Last Updated'),
      ],
      '#default_value' => 'owner',
      '#ajax' => [
        'callback' => '::updateTableCallback',
        'wrapper' => 'lgms-table-wrapper',
        'event' => 'change',
      ],
    ];

    // Table container that will be updated via AJAX.
    $form['table'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'lgms-table-wrapper'],
    ];

    // The buildTable method is used to generate the table.
    // Pass the 'sort_by' value from the form state; if it doesn't exist, default to 'owner'.
    $form['table']['content'] = $this->buildTable($form_state->getValue('sort_by') ?: 'owner');

    return $form;
  }

  protected function buildTable($sort_by = 'owner')
  {
    // Your existing buildTable method code
    $header = [
      ['data' => $this->t('Owner')],
      ['data' => $this->t('Guide Name')],
      ['data' => $this->t('Last Updated')],
      ['data' => $this->t('Subject')],
      ['data' => $this->t('Type')],
    ];

    $mock_data = [
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
    ];

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
      return 0;
    });

    $rows = [];
    foreach ($mock_data as $data) {
      $rows[] = [
        'data' => $data,
      ];
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No entries available.'),
      '#attributes' => ['class' => ['lgms-table']],
    ];
  }

  public function updateTableCallback(array &$form, FormStateInterface $form_state)
  {
    // Your existing updateTableCallback method code
    // Refresh the table part of the form with sorted data.
    $form['table']['content'] = $this->buildTable($form_state->getValue('sort_by'));
    return $form['table'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // Your existing submitForm method code
  }
}
