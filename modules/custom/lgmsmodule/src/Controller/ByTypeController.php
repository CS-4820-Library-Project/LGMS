<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Controller for categorizing and displaying nodes by their "Guide Type" taxonomy term.
 *
 * This controller facilitates the retrieval of nodes based on their associated
 * "Guide Type" taxonomy terms. It then organizes these nodes into groups based
 * on their taxonomy terms, and displays each group in an accordion format, enhancing
 * user navigation and content discoverability on the site.
 */
class ByTypeController {
  /**
   * Generates a render array to display nodes grouped by their "Guide Type" taxonomy terms in an accordion.
   *
   * This method fetches nodes from the database, categorizes them based on their
   * "Guide Type" taxonomy terms, and prepares them for display. Each node is
   * turned into a clickable link, grouped under its corresponding taxonomy term
   * in the accordion. The method relies on a helper function for database operations
   * and another for constructing the accordion layout.
   *
   * @return array
   *   A Drupal render array containing the structured accordion. This accordion
   *   is built with nodes categorized under their respective "Guide Type" taxonomy
   *   terms, facilitating a type-based navigation scheme.
   * @throws EntityMalformedException
   */
  public function byType(): array
  {
    $build = [];
    $data = [];

    // Helper object for database interactions.
    $landingMethods = new helperFunction(\Drupal::database());

    // Retrieve node data from the 'node__field_lgms_guide_type' table.
    $result = $landingMethods->getFromTable('node__field_lgms_guide_type');

    foreach ($result as $record) {
      $nid = $record->entity_id;
      $node = Node::load($nid);

      if ($node) {
        // Load the associated "Guide Type" taxonomy term.
        $term = Term::load($record->field_lgms_guide_type_target_id);
        $taxonomyTerm = $term->label();

        // Generate the link to the node.
        $articleLink = $node->toUrl()->toString();

        // Organize nodes by "Guide Type", with HTML links for display.
        $data[$taxonomyTerm][] = [
          'text' => $node->label(), // The text used for sorting within the group.
          'markup' => new FormattableMarkup('<a href=":link">@name</a>', [
            ':link' => $articleLink,
            '@name' => $node->label()
          ])
        ];
      }
    }

    // Construct the accordion with the categorized data.
    $build['accordion'] = $landingMethods->buildAccordion($data);

    return $build;
  }
}
