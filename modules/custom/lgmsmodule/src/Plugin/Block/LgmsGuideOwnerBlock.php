<?php

namespace Drupal\lgmsmodule\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a 'User Info Block' block.
 *
 * @Block(
 *   id = "lgms_guide_owner_block",
 *   admin_label = @Translation("Contact information"),
 *   category = @Translation("Custom"),
 * )
 */

class LgmsGuideOwnerBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new LgmsGuideOwnerBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $node = $this->routeMatch->getParameter('node');

    // Initialize an array to store unique term names.
    $term_names = [];

    // Ensure we have a node and it's of the type 'guide'.
    if ($node instanceof Node && $node->bundle() === 'guide') {
      // Check if the 'field_lgms_guide_subject' field exists and has value.
      if (!$node->get('field_lgms_guide_subject')->isEmpty()) {
        // Loop through all terms in the multi-value field 'field_lgms_guide_subject'.
        foreach ($node->get('field_lgms_guide_subject')->referencedEntities() as $term) {
          // Add the term name to the array if not already present.
          $term_names[$term->id()] = $term->getName();
        }
      }
    }

    // Add subjects to the content if any exist.
    if (!empty($term_names)) {
      $content["wrapper"]['subjects'] = [
        '#theme' => 'item_list',
        '#items' => array_values($term_names), // Use array_values to reset keys for clean display.
      ];
    }


    $author = $node->getOwner();
    $account = User::load($author->id());

    // Get user name and email.
    $name = $account->getDisplayName();
    $email = $account->getEmail();

    // Get user profile picture.
    $user_picture = '';
    if ($account->hasField('user_picture') && !$account->get('user_picture')->isEmpty()) {
      $user_picture = $account->get('user_picture')->entity->getFileUri();
    }

    $content = [];
    // Render user information.
    $content["wrapper"] = [
      '#type' => 'container',
      '#attributes' => [ 'style' => 'padding: 16px;'],
    ];

    if (!empty($user_picture)) {
      $content["wrapper"]['user_picture'] = [
        '#theme' => 'image',
        '#uri' => $user_picture,
        '#attributes' => [
          'style' => 'width: 243px;height: 287px;object-fit:cover; margin-bottom: 16px;'
        ],

      ];
    }
    if (!empty($name)) {
      $content["wrapper"]['name'] = [
        '#markup' => '<p><strong>Name: </strong>' . $name . '</p>',
      ];
    }
    if (!empty($email)) {
      $content["wrapper"]['email'] = [
        '#markup' => '<p><strong>Email: </strong><a>' . $email . '</a></p>',
      ];
    }

    // Load all nodes of type "guide" created by the current user.
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'guide')
      ->condition('uid', $author->id())
      ->accessCheck(TRUE); // Add access check here.
    $nids = $query->execute();

    // Load and process each guide node.
    foreach ($nids as $nid) {
      $node = Node::load($nid);

      // Get the value of the field_lgms_guide_subject field.
      $target_id = $node->get('field_lgms_guide_subject')->getString();
      $term = Term::load($target_id);
      // Get the actual value of the term.

      if ($term) {
        $term_name = $term->getName();
        $term_names[] = $term_name;
      }
    }

    // Output the term names within a <ul>.
    if (!empty($term_names)) {
      $content["wrapper"]['subject'] = [
        '#markup' => '<p><strong>Subjects:</strong></p>',
      ];

      $content["wrapper"]['subjects'] = [
        '#theme' => 'item_list',
        '#items' => array_unique($term_names),
      ];
    }

    // Wrapper div with padding around content elements.
    return  $content;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Disable caching for this block.
    return 0;
  }
}
