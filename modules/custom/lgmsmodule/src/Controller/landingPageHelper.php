<?php

namespace Drupal\lgmsmodule\Controller;

class landingPageHelper
{
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

  public function getLGMSSearchBar()
  {
    $block_manager = \Drupal::service('plugin.manager.block');
    $config = [];

    $plugin_block = $block_manager->createInstance('lgms_tables_search_block', $config);
    // Return empty render array if user doesn't have access.
    $access_result = $plugin_block->access(\Drupal::currentUser());

    // Return empty render array if user doesn't have access.
    if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {

      return  [];
    }

    $render = $plugin_block->build();
    // Add the cache tags/contexts.
    \Drupal::service('renderer')->addCacheableDependency($render, $plugin_block);

    return $render;
  }
}
