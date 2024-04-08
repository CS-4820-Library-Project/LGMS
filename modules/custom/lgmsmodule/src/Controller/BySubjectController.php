<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Handles displaying nodes categorized by their associated subject taxonomy term.
 *
 * This controller fetches nodes that are tagged with taxonomy terms representing
 * subjects. It organizes these nodes by their subject taxonomy term and displays
 * them in an accordion format. This is particularly useful for creating subject-based
 * navigation or content grouping within the site.
 */
class BySubjectController {
  /**
   * Constructs a render array to display nodes by subject in an accordion layout.
   *
   * Retrieves nodes tagged with subject taxonomy terms from the database,
   * organizes them by their respective terms, and then formats this list for
   * display in an accordion. Each node is presented as a link within the accordion,
   * grouped under its corresponding subject term. This method leverages a helper
   * function to perform database queries and another to construct the accordion.
   *
   * @return array
   *   A render array for Drupal, containing the accordion structure with nodes
   *   grouped by subject taxonomy terms. Each group in the accordion represents a
   *   subject, with the nodes listed as clickable links.
   * @throws EntityMalformedException
   */
  public function bySubject(): array
  {
    $build = [];
    $data = [];

    // Helper object for database interaction.
    $landingMethods = new helperFunction(\Drupal::database());

    // Fetch node information from a custom database table.
    $result = $landingMethods->getFromTable('node__field_lgms_guide_subject');

    foreach ($result as $record) {
      $nid = $record->entity_id;
      $node = Node::load($nid);

      if ($node) {
        // Load the associated subject taxonomy term.
        $term = Term::load($record->field_lgms_guide_subject_target_id);
        $taxonomyTerm = $term->label();

        // Generate the URL to the node.
        $articleLink = $node->toUrl()->toString();

        // Organize nodes by subject term, preparing HTML link for each.
        $data[$taxonomyTerm][] = [
          'text' => $node->label(), // Sortable text.
          'markup' => new FormattableMarkup('<a href=":link">@name</a>', [
            ':link' => $articleLink,
            '@name' => $node->label()
          ])
        ];
      }
    }

    // Construct the accordion structure with the grouped data.
    $build['accordion'] = $landingMethods->buildAccordion($data);

    $build['#cache'] = [
      'tags' => ['node_list:guide'], // Invalidate when guides are added, removed, or updated.
      'contexts' => [
        'user.roles:authenticated', // Different cache for authenticated vs. anonymous users.
      ],
      'max-age' => 3600,
    ];

    return $build;
  }
}
