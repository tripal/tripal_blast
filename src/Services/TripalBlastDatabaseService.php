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
   * Get a specific BlastDB.
   *
   * @param $identifiers
   *   An array of identifiers used to determine which BLAST DB to retrieve.
   *
   * @return
   *   A fully-loaded BLAST DB Node
   */
  public function getDatabaseByIdentifier($identifiers) {
    $condition = [];
    if (isset($identifiers['id'])) {
      $condition['field'] = 'id';
      $condition['value'] = $identifiers['id'];
    }
    elseif (isset($identifiers['name'])) {
      $condition['field'] = 'name';
      $condition['value'] = $identifiers['name'];
    }
    elseif (isset($identifiers['path'])) {
      $condition['field'] = 'path';
      $condition['value'] = $identifiers['path'];
    }

    $storage = \Drupal::entityTypeManager()->getStorage(static::CONFIG_ENTITY_NAME);
    $entity_query = $storage->getQuery();

    $blast_db = $entity_query->condition($condition['field'], $condition['value'])
      ->sort('name', 'ASC')
      ->execute();

    // Load multiples or single item load($id)
    $db = $storage->loadMultiple($blast_db);
    return ($db) ? $db : NULL;
  }

  /**
   * Get BLAST database (configuration entity) by database type (dbtype field).
   * Default to n = nucleotide type database.
   *
   * @param $type
   *   n or p for Nucleotide and Protein BLAST database types, repectively.
   * @return array
   *   Associative array where the key is the database id and value is the name
   *   associated to the id (database name).
   */
  public function getDatabaseByType($type = 'n') {
    // Type can be the full word i.e. nucleotide and protein,
    // or the one character value n and p for nucloetide and protein, respectively.
    $type = $this->translateType($type);

    $storage = \Drupal::entityTypeManager()->getStorage(static::CONFIG_ENTITY_NAME);
    $entity_query = $storage->getQuery();

    $blast_db = $entity_query->condition('dbtype', $type)
    ->sort('name', 'ASC')
    ->execute();

    // Load multiples or single item load($id)
    $all = $storage->loadMultiple($blast_db);
    $db = [];
    foreach($all as $id => $db_obj) {
      $db_id = $db_obj->getId();
      $db_name = $db_obj->getName();

      $db[ $db_id ] = $db_name;
    }

    return $db;
  }

  /**
   * Get database asset (config entity fields).
   *
   * @param string $id
   *   Sixteen-character numeric string, id of database configuration entity.
   *
   * @return array|null
   *   Config entity fields matching the id number given,
   *   or NULL if it does not exist.
   */
  public function getDatabaseConfig($id): ?array {
    if ($id) {
      $config = \Drupal::entityTypeManager()
        ->getStorage(static::CONFIG_ENTITY_NAME)
        ->load($id);

      // If a database was removed, and has no config, return NULL.
      if ($config) {
        return [
          'id'  => $config->getId(),
          'name' => $config->getName(),
          'db_url' => $config->getDbUrl(),
          'path'  => $config->getPath(),
          'dbtype' => $config->getDbType(),
          'db_regexp' => $config->getDbRegExp(),
          'db_id' => $config->getDbId(),
          'db_linkout_type' => $config->getDbLinkout(),
        ];
      }
    }
  }

  /**
   * Translate database type to single character value.
   *
   * @param $type
   *   BLAST database query type ie: nucleotide or protein.
   *
   * @return char
   *   n for nucleotide and p for protein.
   */
  protected function translateType(string $type): string {
    $type = (string) $type;
    if (strlen($type) > 1) {
      return ($type == 'nucleotide') ? 'n' : 'p';
    }

    return $type;
  }

  /**
   * Determine the BLAST program given the type of database and query.
   *
   * @param $database_type
   *   BLAST database type (i.e. nucleotide or protein).
   * @param $query_type
   *   BLAST query type (i.e. nucleotide or protein).
   *
   * @return string
   *   BLAST program: blastn, blastx, tblastn, blastp.
   *
   * @see routing.yml - $type and $program can be parsed using the
   * request url.
   */
  public function getProgramName(string $database_type, string $query_type): ?string {
    $database_type = $this->translateType($database_type);
    $query_type = $this->translateType($query_type);
    $db_types = [
      'n' => [
        'n' => 'blastn',
        'p' => 'blastx'
      ],
      'p' => [
        'n' => 'tblastn',
        'p' => 'blastp'
      ]
    ];

    return $db_types[$database_type][$query_type] ?? NULL;
  }
}
