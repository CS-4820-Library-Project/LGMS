<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Controller responsible for handling the display of content by groups.
 *
 * This controller fetches nodes and categorizes them by a specific taxonomy term,
 * then displays them using a custom accordion built by the helper function.
 */
class ByGroupController {
  /**
   * Builds a render array for displaying nodes categorized by taxonomy terms in an accordion.
   *
   * This method retrieves nodes from the database that are tagged with a specific
   * taxonomy term (guide group). It then organizes these nodes by their taxonomy term,
   * constructs links to each node, and finally displays this organized list in an
   * accordion format on the page.
   *
   * @return array
   *   A render array that Drupal will use to render the categorized content in an accordion format.
   * @throws EntityMalformedException
   */
  public function byGroup(): array
  {
    $build = [];
    $data = [];

    // Initialize helper function for database operations.
    $landingMethods = new helperFunction(\Drupal::database());

    // Fetches records from 'node__field_lgms_guide_group' table.
    $result = $landingMethods->getFromTable('node__field_lgms_guide_group');

    foreach ($result as $record) {
      $nid = $record->entity_id;
      $node = Node::load($nid);

      if($node) {
        // Load the taxonomy term associated with this node.
        $term = Term::load($record->field_lgms_guide_group_target_id);
        $taxonomyTerm = $term->label();

        // Generate the URL for the node.
        $articleLink = $node->toUrl()->toString();

        // Organize data by taxonomy term, with links formatted in HTML.
        $data[$taxonomyTerm][] = [
          'text' => $node->label(), // Text to be used for sorting.
          'markup' => new FormattableMarkup('<a href=":link">@name</a>',
            [':link' => $articleLink, '@name' => $node->label()])
        ];
      }
    }

    // Use the helper function to build an accordion with the organized data.
    $build['accordion'] = $landingMethods->buildAccordion($data);

    return $build;
  }
}
