<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Handles dynamic generation of popup modals or tabs for forms.
 *
 * This controller interprets URL query parameters to dynamically generate
 * and display one or more forms in a modal or tabbed interface. It supports
 * displaying a single form directly or multiple forms within a tabbed layout,
 * based on the provided 'ids' and 'forms' query parameters.
 */
class PopupModalController extends ControllerBase {

  /**
   * Builds and returns a render array for displaying forms in a modal or tabbed layout.
   *
   * This method reads 'ids' and 'forms' from the URL query parameters, decodes
   * them from JSON, and uses this information to build the corresponding forms.
   * For a single form, it directly returns the form render array. For multiple forms,
   * it constructs a tabbed interface with each tab containing one form.
   *
   * @return array
   *   A render array representing either a single form or a tabbed interface containing
   *   multiple forms, depending on the URL query parameters.
   */
  public function build(): array
  {
    $ids = \Drupal::request()->query->get('ids');
    $forms = \Drupal::request()->query->get('forms');

    // Decode JSON parameters if present.
    if ($ids) {
      $json_data = urldecode($ids);
      $ids = json_decode($json_data);
    }

    if ($forms) {
      $json_data = urldecode($forms);
      $forms = json_decode($json_data);
    }

    // Handle single form scenario.
    if (sizeof((array)$forms) == 1) {
      $form_class = 'Drupal\lgmsmodule\Form\\' . str_replace(' ', '', $forms[0]->form);
      return \Drupal::formBuilder()->getForm($form_class, $ids);
    }
    // Handle multiple forms scenario.
    else {
      $modal_container = [
        '#type' => 'container',
        '#attributes' => ['class' => ['horizontal-tabs-modal']],
        '#attached' => [
          'library' => ['lgmsmodule/lgmsmodule'], // Attach necessary libraries.
        ],
      ];

      // Construct tab navigation markup.
      $tab_markup = '<div class="nav-container"><ul class="tabs-list">';
      $tab_contents = [];

      foreach ($forms as $form) {
        $tab_name = strtolower(str_replace(' ', '-', $form->name));
        $href = '#' . $tab_name;
        $tab_markup .= "<li><a href=\"{$href}\" class=\"tab-link nav-button\">{$form->name}</a></li>";

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

      // Combine tab navigation and contents into the modal container.
      $modal_container['tabs'] = [
        '#type' => 'markup',
        '#markup' => $tab_markup,
      ];
      $modal_container['tabs']['content'] = $tab_contents;

      return $modal_container;
    }
  }
}
