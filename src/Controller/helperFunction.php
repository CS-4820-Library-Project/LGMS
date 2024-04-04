<?php

namespace Drupal\lgmsmodule\Controller;

use Drupal\Core\Database\Connection;

class helperFunction
{

  protected Connection $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public function getLink(String $nid): string
  {
    return 'http://' . $_SERVER['HTTP_HOST'] . \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $nid])->toString();
  }

  public function buildAccordion($data): array
  {
    $accordion_items = [];
    ksort($data); //Sort accordions

    foreach ($data as $title => $details) {
      //Sort the list inside accordion
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

  public function getLGMSSearchBar(String $blockID, String $type)
  {
    $block_manager = \Drupal::service('plugin.manager.block');
    $config = [];

    $plugin_block = $block_manager->createInstance($blockID, $config);
    // Return empty render array if user doesn't have access.
    $access_result = $plugin_block->access(\Drupal::currentUser());

    // Return empty render array if user doesn't have access.
    if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {

      return  [];
    }

    $plugin_block->setType($type);
    $render = $plugin_block->build();
    // Add the cache tags/contexts.
    \Drupal::service('renderer')->addCacheableDependency($render, $plugin_block);

    return $render;
  }

  public function getFromTable(String $tableName): array {
    $splitName = explode('__', $tableName);
    $field_name = end($splitName);
    $termColName = $field_name . '_target_id';

    // Assuming that the field is also attached to the 'guide' content type
    $query = $this->database->select($tableName, 'n')
      ->condition('n.bundle', 'guide') // Changed from 'article' to 'guide'
      ->fields('n', ['entity_id', $termColName]);

    return $query->execute()->fetchAll();
  }

}
