<?php

namespace Drupal\lgmsmodule\Controller;


use Drupal\Core\Controller\ControllerBase;

class GuidePageModalController extends ControllerBase {

  public function build() {

    $modal_container = [
      '#type' => 'container',
      '#attributes' => ['class' => ['horizontal-tabs-modal']],
      '#attached' => [
        'library' => [
          'lgmsmodule/lgmsmodule', // Assuming your JS is properly defined here
        ],
      ]
    ];

    $current_guide_id = \Drupal::request()->query->get('current_guide');

    // Add the first tab with the add_guide_page form, include current_guide parameter
    $addForm = \Drupal::formBuilder()->getForm('Drupal\lgmsmodule\Form\CreateGuidePageForm', $current_guide_id);
    // Add the second tab with the import_guide_page form, include current_guide parameter
    $importForm = \Drupal::formBuilder()->getForm('Drupal\lgmsmodule\Form\GuidePageImportForm', $current_guide_id);

    $modal_container['tabs'] = [
      '#type' => 'markup',
      '#markup' => '<div class="nav-container"><ul class="tabs-list">
                    <li><a href="#addForm" class="tab-link nav-button">Add Guide</a></li>
                    <li><a href="#importForm" class="tab-link nav-button">Import Guide</a></li>
                  </ul></div>',
    ];

    $modal_container['tabs']['content'] = [
      'addForm' => [
        '#type' => 'container',
        '#attributes' => ['id' => 'addForm', 'class' => ['tab-content']],
        'form' => $addForm,
      ],
      'importForm' => [
        '#type' => 'container',
        '#attributes' => ['id' => 'importForm', 'class' => ['tab-content']],
        'form' => $importForm,
      ],
    ];

    return $modal_container;
  }
}
