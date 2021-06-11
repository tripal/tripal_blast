<?php
/**
 * @file
 * Contains class definition of Tripal BLAST Database service.
 * All results are from configuration entity.
 */
namespace Drupal\tripal_blast\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\tripal_blast\TripalBlastDatabaseInterface;


class TripalBlastDatabaseService {
  const CONFIG_ENTITY_NAME = 'tripalblastdatabase';
  
  /**
   * Get BLAST database (configuration entity) by database type (dbtype field).
   * Default to n = nucleotide type database.
   * 
   * @param $type
   *   n or p for Nucleotide and Protein BLAST database types, repectively.
   * @return arra
   *   Associative array where the key is the database id and value is the name
   *   associated to the id (database name).
   */
  public function getDatabaseByType($type = 'n') {
    $database_entity = \Drupal::entityTypeManager()->getStorage(static::CONFIG_ENTITY_NAME);
    $entity_query = $database_entity->getQuery();

    $blast_db = $entity_query->condition('dbtype', $type)
    ->sort('name', 'ASC')
    ->execute();
   
    // Load multiples or single item load($id)
    $all = $database_entity->loadMultiple($blast_db);
    $db = [];
    foreach($all as $id => $db_obj) {
      $db_id = $db_obj->getId();
      $db_name = $db_obj->getName();
      
      $db[ $db_id ] = $db_name;
    }

    return $db;
  }
}