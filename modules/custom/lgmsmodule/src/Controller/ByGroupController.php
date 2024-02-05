<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\lgmsmodule\sql\sqlMethods;

class ByGroupController {
  public function byGroup(){
    $build = [];
    $data = [];
    $sqlMethods = new sqlMethods(\Drupal::database());
    $landingMethods = new landingPageHelper();

    $result = $sqlMethods->getFromTable('node__field_lgms_guide_group');

    foreach ($result as $record) {
      $nid = $record->entity_id;

      // Get the article title.
      $title = $sqlMethods->getTitle($nid);

      // Get the taxonomy term.
        $taxonomyTerm = $sqlMethods->getTaxonomyTerm($record->field_lgms_guide_group_target_id);

      // Construct the article link.
      $articleLink = $landingMethods->getLink($nid);

      // Build the row with the article link and title.
      $data[$taxonomyTerm][] = new FormattableMarkup('<a href=":link">@name</a>',
        [':link' => $articleLink,
          '@name' => $title]);
    }

    $build['accordion'] = $landingMethods->buildAccordion($data);

    return $build;
  }
}
