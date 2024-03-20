<?php

namespace Drupal\lgmsmodule\sql;

use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class sqlMethods {
  protected Connection $database;

  public function __construct(Connection $database) {
    $this->database = $database;
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
