<?php

namespace Drupal\lgmsmodule\sql;

use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class sqlMethods {
  protected Connection $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  public function getTitle(String $nid) {
    // Get the guide title from the node_field_data table.
    $query = $this->database->select('node_field_data', 'nd')
      ->fields('nd', ['title'])
      ->condition('nd.nid', $nid)
      ->condition('nd.status', 1)
      ->condition('nd.type', 'guide'); // Added condition for 'guide' content type

    return $query->execute()->fetchField();
  }

  public function getTaxonomyTerm(String $tid) {
    // Get the taxonomy term name from the taxonomy_term_field_data table.
    $query = $this->database->select('taxonomy_term_field_data', 'td')
      ->fields('td', ['name'])
      ->condition('td.tid', $tid);

    return $query->execute()->fetchField();
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

  public function getGuides(): array {
    $query = $this->database->select('node_field_data', 'n')
      ->condition('n.type', 'guide') // Changed from 'article' to 'guide'
      ->condition('n.status', 1)
      ->fields('n', ['title', 'uid', 'nid', 'changed']);

    return $query->execute()->fetchAllAssoc('nid');
  }

  public function getOwner($uid) {
    $query = $this->database->select('users_field_data', 'u')
      ->fields('u', ['name'])
      ->condition('u.uid', $uid);

    return $query->execute()->fetchField();
  }

  public function getGuidePages($guide_id) {

    $query = $this->database->select('node_field_data', 'n')
      ->fields('n', ['nid', 'title'])
      ->condition('n.status', 1)
      ->condition('n.type', 'guide_page');

    // Join with the field table that references the guide.
    $query->join('node__field_parent_guide', 'ref', 'n.nid = ref.entity_id');
    $query->condition('ref.field_parent_guide_target_id', $guide_id);



    return $query->execute()->fetchAll();
  }
}
