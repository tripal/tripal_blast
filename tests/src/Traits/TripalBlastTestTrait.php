<?php
namespace Drupal\Tests\tripal_blast\Traits;

use Drupal\tripal_blast\Entity\TripalBlastDatabase;

/**
 * Provides functions to aid testing of this module.
 */
trait TripalBlastTestTrait {

  /**
   * Create a Tripal BLAST database config entity.
   *
   * @param array $values
   *   An associative array of values to set for the blast database.
   *   Supported keys include:
   *    - name: The human-readable name of the blast database.
   *    - path: The file path to the blast database.
   *    - dbtype: The type of the blast database.
   *    - dbxref_id_regexp: The regular expression for extracting database IDs.
   *    - dbxref_db_id: The ID of the database reference.
   *    - dbxref_linkout_type: The type of linkout for the database reference.
   *
   * @return \Drupal\tripal_blast\Entity\TripalBlastDatabase
   *   The created Tripal BLAST database config entity.
   */
  public function createBlastDatabase(array $values): TripalBlastDatabase {

    // Generate default values.
    $random = $this->getRandomGenerator();
    $values['id'] ??= rand(1000000, 2000000);
    $values['name'] ??= $random->sentences(4, TRUE);
    $values['path'] ??= '/var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas_reinhardtii_v5.6.nsq';
    $values['dbtype'] ??= 'n';
    $values['dbxref_id_regexp'] ??= '^>.*$';
    $values['dbxref_db_id'] ??= 1;
    $values['dbxref_linkout_type'] ??= 'none';

    // Now create it.
    $database = TripalBlastDatabase::create($values);
    $database->save();

    return $database;
  }

}
