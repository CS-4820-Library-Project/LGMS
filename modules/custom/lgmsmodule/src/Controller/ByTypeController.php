<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\lgmsmodule\sql\sqlMethods;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

class ByTypeController {
  public function byType(){
    $build = [];
    $data = [];
    $sqlMethods = new sqlMethods(\Drupal::database());
    $landingMethods = new landingPageHelper();

    $result = $sqlMethods->getFromTable('node__field_lgms_guide_type');

    foreach ($result as $record) {
      $nid = $record->entity_id;

      $node = Node::load($nid);

      // Get the article title.
      $title = $node->label();

      if($title != null){
        // Get the taxonomy term.
        $term = Term::load($record->field_lgms_guide_type_target_id);
        $taxonomyTerm = $term->label();

        // Construct the article link.
        $articleLink = $node->toUrl()->toString();

        // Build the row with the article link and title.
        $data[$taxonomyTerm][] = [
          'text' => $title, // Text to sort by
          'markup' => new FormattableMarkup('<a href=":link">@name</a>',
            [':link' => $articleLink,
              '@name' => $title]
          )
        ];
      }
    }

    $build['accordion'] = $landingMethods->buildAccordion($data);

    return $build;
  }
}
