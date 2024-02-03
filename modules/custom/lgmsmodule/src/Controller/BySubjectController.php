<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\lgmsmodule\sql\sqlMethods;

class BySubjectController {
  public function bySubject(){
    $build = [];
    $data = [];
    $sqlMethods = new sqlMethods(\Drupal::database());
    $landingMethods = new landingPageHelper();

    $result = $sqlMethods->getFromTable('	node__field_guide_subject');

    foreach ($result as $record) {
      $nid = $record->entity_id;

      // Get the article title.
      $title = $sqlMethods->getTitle($nid);

      // Get the taxonomy term.
      $taxonomyTerm = $sqlMethods->getTaxonomyTerm($record->field_guide_subject_target_id);

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
