<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SortingForm extends FormBase {

  public function getFormId() {
    return 'lgmsmodule_sorting_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
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

  protected function buildTable($sort_by = 'owner') {
    $header = [
      ['data' => $this->t('Owner')],
      ['data' => $this->t('Guide Name')],
      ['data' => $this->t('Last Updated')],
    ];

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
    ];
  }

  public function updateTableCallback(array &$form, FormStateInterface $form_state) {
    // Refresh the table part of the form with sorted data.
    $form['table']['content'] = $this->buildTable($form_state->getValue('sort_by'));
    return $form['table'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No submission logic needed for the AJAX-driven form.
  }

}
