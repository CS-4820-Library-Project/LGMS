<?php

namespace Drupal\lgmsmodule\Controller;


use Drupal\Core\Controller\ControllerBase;

class PopupModalController extends ControllerBase {

  public function build() {

    $ids = \Drupal::request()->query->get('ids');
    $forms = \Drupal::request()->query->get('forms');

    if($ids){
      $json_data = urldecode($ids);
      $ids = json_decode($json_data);
    }

    if ($forms) {
      $json_data = urldecode($forms);
      $forms = json_decode($json_data);
    }

    if(sizeof((array)$forms) ==  1){
      $form_class = 'Drupal\lgmsmodule\Form\\' . str_replace(' ', '', $forms[0]->form);
      return \Drupal::formBuilder()->getForm($form_class, $ids);
    }
    else {
      $modal_container = [
        '#type' => 'container',
        '#attributes' => ['class' => ['horizontal-tabs-modal']],
        '#attached' => [
          'library' => [
            'lgmsmodule/lgmsmodule', // Assuming your JS is properly defined here
          ],
        ]
      ];

      $tab_markup = '<div class="nav-container"><ul class="tabs-list">';
      $tab_contents = [];

      foreach ($forms as $form) {
        $tab_name = strtolower(str_replace(' ', '-', $form->name));
        $href = '#' . $tab_name;
        $tab_markup .= '<li><a href="' . $href . '" class="tab-link nav-button">' . $form->name . '</a></li>';
        $form_class = 'Drupal\lgmsmodule\Form\\' . str_replace(' ', '', $form->form);
        $formObject = \Drupal::formBuilder()->getForm($form_class, $ids);
        $key = lcfirst(str_replace(' ', '', $form->name));
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
}
