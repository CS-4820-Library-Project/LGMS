<?php

namespace Drupal\lgmsmodule\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a 'User Info Block' block.
 *
 * @Block(
 *   id = "lgms_guide_owner_block",
 *   admin_label = @Translation("Lgms Guide Owner Block"),
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

    $author = $node->getOwner();
    $account = \Drupal\user\Entity\User::load($author->id());

    // Get user name and email.
    $name = $account->getDisplayName();
    $email = $account->getEmail();

    // Get user profile picture.
    $user_picture = '';
    if ($account->hasField('user_picture') && !$account->get('user_picture')->isEmpty()) {
      $user_picture = $account->get('user_picture')->entity->getFileUri();
    }

    // Render user information.
    $content = [];
    if (!empty($user_picture)) {
      $content['user_picture'] = [
        '#theme' => 'image_style',
        '#style_name' => 'thumbnail',
        '#uri' => $user_picture,
      ];
    }
    if (!empty($name)) {
      $content['name'] = [
        '#markup' => '<p>' . $name . '</p>',
      ];
    }
    if (!empty($email)) {
      $content['email'] = [
        '#markup' => '<p>' . $email . '</p>',
      ];
    }

    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Disable caching for this block.
    return 0;
  }
}
