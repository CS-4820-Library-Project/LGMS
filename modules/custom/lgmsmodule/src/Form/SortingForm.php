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

    $form['search'] = [
      '#type' => 'search',
      '#attributes' => ['class' => ['lgms-search'], 'placeholder' => $this->t('Search by guide name...'),],
    ];

    $form['sort_by'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort by'),
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

    $form['table'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'lgms-table-wrapper'],
    ];

    $form['table']['content'] = $this->buildTable($form_state->getValue('sort_by') ?: 'owner');

    return $form;
  }

  protected function buildTable($sort_by = 'owner')
  {
    $header = [
      ['data' => $this->t('Owner')],
      ['data' => $this->t('Guide Name')],
      ['data' => $this->t('Last Updated')],
      ['data' => $this->t('Subject')],
      ['data' => $this->t('Type')],
    ];

    $mock_data = [
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
    // Refresh the table part of the form with sorted data.
    $form['table']['content'] = $this->buildTable($form_state->getValue('sort_by'));
    return $form['table'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // No submission logic needed for the AJAX-driven form.
  }
}
