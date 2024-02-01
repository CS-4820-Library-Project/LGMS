<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Render\FormattableMarkup;

class SortingForm extends FormBase
{

  public function getFormId()
  {
    return 'lgmsmodule_sorting_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state,  $headers = NULL, $rows = NULL)
  {
    // Build a dropdown menu for sorting
    $form['sort_by'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort by'),
      '#options' => [
        'guide_name_up' => $this->t('Guide Name &#9650;'),
        'guide_name_down' => $this->t('Guide Name &#9660;'),

        'owner_up' => $this->t('Owner &#9650;'),
        'owner_down' => $this->t('Owner &#9660;'),

        'last_updated_up' => $this->t('Last Updated &#9650;'),
        'last_updated_down' => $this->t('Last Updated &#9660;'),
      ],
      '#default_value' => 'guide_name_up',
      '#ajax' => [
        'callback' => '::updateTableCallback',
        'wrapper' => 'lgms-table-wrapper',
        'event' => 'change',
      ],
    ];

    // Table Wrapper
    $form['table_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'lgms-table-wrapper'],
    ];


    $sort_by = $form_state->getValue('sort_by') ?: 'guide_name_up';

    // Sort the Rows
    usort($rows, function ($a, $b) use ($sort_by) {
      return match ($sort_by) {
        'guide_name_up' => strcmp(strip_tags($a[0]['data']), strip_tags($b[0]['data'])),
        'owner_up' => strcmp($a[1], $b[1]),
        'last_updated_up' => strcmp($a[2], $b[2]),

        'guide_name_down' => strcmp(strip_tags($b[0]['data']), strip_tags($a[0]['data'])),
        'owner_down' => strcmp($b[1], $a[1]),
        'last_updated_down' => strcmp($b[2], $a[2]),
        default => 0,
      };
    });

    // Build the Table
    $form['table_wrapper']['content'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No entries available.'),
      '#attributes' => ['class' => ['lgms-table']],
    ];

    return $form;
  }

  public function updateTableCallback(array $form, FormStateInterface $form_state)
  {
    // Refresh the table part of the form with sorted data.
    return $form['table_wrapper'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // No submission logic needed for the AJAX-driven form.
  }
}

