<?php

namespace Drupal\Tests\tripal_blast\Kernel;

use Drupal\Tests\tripal\Kernel\TripalTestKernelBase;
use Drupal\tripal_blast\Entity\TripalBlastDatabase;
use Drupal\tripal_blast\Services\TripalBlastDatabaseService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Tripal BLAST database service.
 *
 * @group Tripal
 * @group TripalBlast
 * @group TripalBlastDatabaseService
 */
#[Group('tripal-blast')]
#[RunTestsInSeparateProcesses]
class TripalBlastDatabaseServiceTest extends TripalTestKernelBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'tripal', 'tripal_blast'];

  protected static $testdb_details = [
    [
      'id' => 'clamy_nuc',
      'name' => 'Chlamydomonas reinhardtii Nucleotide DB',
      'path' => '/var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas_reinhardtii_v5.6.nin',
      'dbtype' => 'n',
      'dbxref_id_regexp' => '^>.*$',
      'dbxref_db_id' => 1,
      'dbxref_linkout_type' => 'none',
    ],
    [
      'id' => 'clamy_prot',
      'name' => 'Chlamydomonas reinhardtii Protein DB',
      'path' => '/var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas_reinhardtii_v5.6_protein.nin',
      'dbtype' => 'p',
      'dbxref_id_regexp' => '^>.*$',
      'dbxref_db_id' => 2,
      'dbxref_linkout_type' => 'none',
    ]
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::state()->set('is_a_test_environment', TRUE);
    $this->installConfig('system');
  }

  /**
   * Create sample BLAST database config entities for testing.
   */
  protected function createSampleDatabases(): void {

    // Create out test databases.
    foreach (self::$testdb_details as $values) {
      $database = TripalBlastDatabase::create($values);
      $database->save();
    }
  }

  /**
   * Tests retrieval of BLAST databases by type.
   */
  public function testGetDatabaseByType() {
    $this->createSampleDatabases();

    $this->markTestIncomplete('Still working on this test.');

    $service = \Drupal::service('tripal_blast.database_service');
    $this->assertInstanceOf(TripalBlastDatabaseService::class, $service);

    $nucleotide_databases = $service->getDatabaseByType('n');
    $this->assertArrayHasKey('clamy_nuc', $nucleotide_databases);
    $this->assertSame('Chlamydomonas reinhardtii Nucleotide DB', $nucleotide_databases['clamy_nuc']);

    $protein_databases = $service->getDatabaseByType('protein');
    $this->assertArrayHasKey('clamy_prot', $protein_databases);
    $this->assertSame('Chlamydomonas reinhardtii Protein DB', $protein_databases['clamy_prot']);
  }

  /**
   * Tests retrieval of BLAST databases by identifier and config values.
   */
  public function testGetDatabaseByIdentifierAndConfig() {
    $this->createSampleDatabases();

    $service = \Drupal::service('tripal_blast.database_service');

    $databases = $service->getDatabaseByIdentifier(['name' => 'Test Protein DB']);
    $this->assertIsArray($databases);
    $this->assertArrayHasKey('db_protein', $databases);

    $config = $service->getDatabaseConfig('db_nucleotide');
    $this->assertSame('db_nucleotide', $config['id']);
    $this->assertSame('/tmp/db_nucleotide', $config['path']);
    $this->assertSame('n', $config['dbtype']);
  }

  /**
   * Tests program name translation based on database type and program.
   */
  public function testGetProgramName() {
    $service = \Drupal::service('tripal_blast.database_service');

    $this->assertSame('blastn', $service->getProgramName('n', 'nucleotide'));
    $this->assertSame('tblastn', $service->getProgramName('p', 'nucleotide'));
    $this->assertSame('blastp', $service->getProgramName('p', 'protein'));
  }

}
