<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\Core\Database\Connection;

/**
 * Provides helper functions for the LGMS module.
 *
 * This class contains methods to assist with common tasks such as generating links,
 * building accordion structures, fetching blocks, and querying the database.
 * It serves as a utility class for the module, centralizing functionality needed
 * across multiple controllers.
 */
class helperFunction {

  /**
   * The database connection.
   *
   * @var Connection
   */
  protected Connection $database;

  /**
   * Constructs a new helperFunction object.
   *
   * @param Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Generates a fully qualified URL for a given node ID.
   *
   * @param string $nid
   *   The node ID.
   *
   * @return string
   *   The fully qualified URL to the node.
   */
  public function getLink(string $nid): string {
    return 'http://' . $_SERVER['HTTP_HOST'] . \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $nid])->toString();
  }

  /**
   * Builds an accordion structure from the provided data.
   *
   * @param array $data
   *   An associative array where keys are accordion titles and values are arrays
   *   of items containing 'text' and 'markup' to display inside each accordion.
   *
   * @return array
   *   A render array representing an accordion structure.
   */
  public function buildAccordion(array $data): array {
    $accordion_items = [];
    ksort($data); // Sort accordions by title.

    foreach ($data as $title => $details) {
      // Sort the list items inside each accordion alphabetically.
      usort($details, function ($a, $b) {
        return strcmp($a['text'], $b['text']);
      });

      $formattedDetails = array_map(function ($item) {
        return $item['markup'];
      }, $details);

      $accordion_items[] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $title,
        '#children' => [
          '#theme' => 'item_list',
          '#items' => $formattedDetails,
        ],
      ];
    }

    return $accordion_items;
  }

  /**
   * Retrieves and prepares a block for rendering based on the given block ID and type.
   *
   * @param string $blockID
   *   The block plugin ID.
   * @param string $type
   *   The block type.
   *
   * @return array
   *   A render array for the block if the current user has access; otherwise, an empty array.
   */
  public function getLGMSSearchBar(string $blockID, string $type): array
  {
    $block_manager = \Drupal::service('plugin.manager.block');
    $config = [];

    $plugin_block = $block_manager->createInstance($blockID, $config);
    $access_result = $plugin_block->access(\Drupal::currentUser());

    if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {
      return [];
    }

    $plugin_block->setType($type);
    $render = $plugin_block->build();
    \Drupal::service('renderer')->addCacheableDependency($render, $plugin_block);

    return $render;
  }

  /**
   * Fetches records from a specified table and returns them as an array.
   *
   * @param string $tableName
   *   The table name to query.
   *
   * @return array
   *   An array of objects representing the fetched records.
   */
  public function getFromTable(string $tableName): array {
    $splitName = explode('__', $tableName);
    $field_name = end($splitName);
    $termColName = $field_name . '_target_id';

    $query = $this->database->select($tableName, 'n')
      ->condition('n.bundle', 'guide') // Assumes the field is attached to the 'guide' content type.
      ->fields('n', ['entity_id', $termColName]);

    return $query->execute()->fetchAll();
  }
}
