<?php

namespace Drupal\lgmsmodule\Controller;
class landingPageHelper {
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
}
