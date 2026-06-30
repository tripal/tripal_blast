<?php

namespace Drupal\Tests\tripal_blast\Kernel;

use Drupal\Tests\tripal\Kernel\TripalTestKernelBase;
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
    $storage = \Drupal::entityTypeManager()->getStorage('tripalblastdatabase');

    $storage->create([
      'id' => 'db_nucleotide',
      'name' => 'Test Nucleotide DB',
      'path' => '/tmp/db_nucleotide',
      'dbtype' => 'n',
      'dbxref_id_regexp' => '^>.*$',
      'dbxref_db_id' => 1,
      'dbxref_linkout_type' => 'none',
    ])->save();

    $storage->create([
      'id' => 'db_protein',
      'name' => 'Test Protein DB',
      'path' => '/tmp/db_protein',
      'dbtype' => 'p',
      'dbxref_id_regexp' => '^>.*$',
      'dbxref_db_id' => 2,
      'dbxref_linkout_type' => 'none',
    ])->save();
  }

  /**
   * Tests retrieval of BLAST databases by type.
   */
  public function testGetDatabaseByType() {
    $this->createSampleDatabases();

    $service = \Drupal::service('tripal_blast.database_service');
    $this->assertInstanceOf(TripalBlastDatabaseService::class, $service);

    $nucleotide_databases = $service->getDatabaseByType('n');
    $this->assertArrayHasKey('db_nucleotide', $nucleotide_databases);
    $this->assertSame('Test Nucleotide DB', $nucleotide_databases['db_nucleotide']);

    $protein_databases = $service->getDatabaseByType('protein');
    $this->assertArrayHasKey('db_protein', $protein_databases);
    $this->assertSame('Test Protein DB', $protein_databases['db_protein']);
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
