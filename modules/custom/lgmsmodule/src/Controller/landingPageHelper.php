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
    foreach ($data as $title => $details) {
      $accordion_items[] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $title,
        '#children' => [
          '#theme' => 'item_list',
          '#items' => $details,
        ],
      ];
    }

    return $accordion_items;
  }
}
