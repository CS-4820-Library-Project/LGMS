<?php

namespace Drupal\lgmsmodule\Controller;


use Drupal\Core\Controller\ControllerBase;

class PopupModalController extends ControllerBase {

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

    $current_id = \Drupal::request()->query->get('current_id');
    $forms = \Drupal::request()->query->get('forms');
    $tab_markup = '<div class="nav-container"><ul class="tabs-list">';
    $tab_contents = [];

    if($forms){
      $json_data = urldecode($forms);
      $forms = json_decode($json_data);
    }

    foreach ($forms as $form) {
      $tab_name = strtolower(str_replace(' ', '-', $form->name));
      $href = '#' . $tab_name;
      $tab_markup .= '<li><a href="' . $href . '" class="tab-link nav-button">' .$form->name . '</a></li>';
      $form_class = 'Drupal\lgmsmodule\Form\\' . str_replace(' ', '', $form->form);
      $formObject = \Drupal::formBuilder()->getForm($form_class, $current_id);
      $key = lcfirst(str_replace(' ', '',  $form->name));
      $tab_contents[$key] = [
        '#type' => 'container',
        '#attributes' => ['id' => $tab_name, 'class' => ['tab-content']],
        'form' => $formObject,
      ];
    }
    $tab_markup .= '</ul></div>';

    $modal_container['tabs'] = [
      '#type' => 'markup',
      '#markup' => $tab_markup,
    ];

    $modal_container['tabs']['content'] = $tab_contents;

    return $modal_container;
  }
}
