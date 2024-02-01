<?php

namespace Drupal\lgmsmodule\sql;

use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SqlMethods {
  protected Connection $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('database')
    );
  }

  public function getTitle(String $nid){
    // Get the article title from the node_field_data table.
    $query = $this->database->select('node_field_data', 'nd')
      ->fields('nd', ['title'])
      ->condition('nd.nid', $nid);

    return $query->execute()->fetchField();
  }

  public function getTaxonomyTerm(String $tid){
    // Get the article title from the node_field_data table.
    $query = $this->database->select('taxonomy_term_field_data', 'td')
      ->fields('td', ['name'])
      ->condition('td.tid', $tid);

    return $query->execute()->fetchField();
  }

  public function getFromTable(String $tableName): array
  {
    $splitName = explode('__', $tableName);
    $field_name = end($splitName);
    $termColName = $field_name . '_target_id';

    $query = $this->database->select($tableName, 'n')
      ->condition('n.bundle', 'article')
      ->fields('n', ['entity_id', $termColName]);

    return $query->execute()->fetchAll();
  }

  public function getGuides(): array
  {
    $query = $this->database->select('node_field_data', 'n')
      ->condition('n.type', 'article')
      ->condition('n.status', 1)
      ->fields('n', ['title', 'uid', 'nid', 'changed']);

    return $query->execute()->fetchAllAssoc('nid');
  }

  public function getOwner($uid)
  {
    $query = $this->database->select('users_field_data', 'u')
      ->fields('u', ['name'])
      ->condition('u.uid', $uid);

    return $query->execute()->fetchField();
  }
}
