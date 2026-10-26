<?php

namespace Drupal\tripal_blast\Services;

/**
 * This service handles tripal blast databases.
 */
class TripalBlastDatabaseService {
  const CONFIG_ENTITY_NAME = 'tripalblastdatabase';

  /**
   * Get a specific BlastDB.
   *
   * @param array $identifiers
   *   An array of identifiers used to determine which BLAST DB to retrieve.
   *
   * @return object|null
   *   A fully-loaded BLAST DB Node.
   */
  public function getDatabaseByIdentifier(array $identifiers) {
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
   *
   * Default to n = nucleotide type database.
   *
   * @param string $type
   *   Is 'n' or 'p' for Nucleotide or Protein BLAST database types,
   *   repectively.
   *
   * @return array
   *   Associative array where the key is the database id and the value is
   *   the name associated to the id (database name).
   */
  public function getDatabaseByType($type = 'n') {
    // Type can be the full word i.e. 'nucleotide' or 'protein',
    // or the one character value 'n' or 'p' for nucloetide or
    // protein, respectively.
    $type = $this->translateType($type);

    $storage = \Drupal::entityTypeManager()->getStorage(static::CONFIG_ENTITY_NAME);
    $entity_query = $storage->getQuery();

    $blast_db = $entity_query->condition('dbtype', $type)
      ->sort('name', 'ASC')
      ->execute();

    // Load multiples or single item load($id)
    $all = $storage->loadMultiple($blast_db);
    $db = [];
    foreach ($all as $db_obj) {
      $db_id = $db_obj->getId();
      $db_name = $db_obj->getName();

      $db[$db_id] = $db_name;
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
   * @param string $type
   *   BLAST database query type i.e.: 'nucleotide' or 'protein'.
   *
   * @return string
   *   Returns 'n' for nucleotide or 'p' for protein.
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
   * @param string $database_type
   *   BLAST database type (i.e. 'nucleotide' or 'protein').
   * @param string $query_type
   *   BLAST query type (i.e. 'nucleotide' or 'protein').
   *
   * @return string|null
   *   BLAST program: blastn, blastx, tblastn, blastp, or NULL if
   *   invalid values were passed.
   *
   * @see routing.yml
   * $type and $program can be parsed using the request url.
   */
  public function getProgramName(string $database_type, string $query_type): ?string {
    $database_type = $this->translateType($database_type);
    $query_type = $this->translateType($query_type);
    $db_types = [
      'n' => [
        'n' => 'blastn',
        'p' => 'blastx',
      ],
      'p' => [
        'n' => 'tblastn',
        'p' => 'blastp',
      ],
    ];

    return $db_types[$database_type][$query_type] ?? NULL;
  }

}
