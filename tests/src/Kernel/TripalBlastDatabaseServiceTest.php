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
      'id' => 123450,
      'name' => 'Chlamydomonas reinhardtii Nucleotide DB',
      'path' => '/var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas_reinhardtii_v5.6.nin',
      'dbtype' => 'n',
      'dbxref_id_regexp' => '^>.*$',
      'dbxref_db_id' => 1,
      'dbxref_linkout_type' => 'none',
    ],
    [
      'id' => 67890,
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

    $service = \Drupal::service('tripal_blast.database_service');
    $this->assertInstanceOf(TripalBlastDatabaseService::class, $service);

    // Test retrieval of nucleotide db using short param.
    $nucleotide_databases = $service->getDatabaseByType('n');
    $this->assertArrayHasKey(123450, $nucleotide_databases);
    $this->assertSame('Chlamydomonas reinhardtii Nucleotide DB', $nucleotide_databases[123450]);

    // Test retrieval of protein db using short param.
    $protein_databases = $service->getDatabaseByType('p');
    $this->assertArrayHasKey(67890, $protein_databases);
    $this->assertSame('Chlamydomonas reinhardtii Protein DB', $protein_databases[67890]);

    // Test retrieval of nucleotide db using long param.
    $nucleotide_databases = $service->getDatabaseByType('n');
    $this->assertArrayHasKey(123450, $nucleotide_databases);
    $this->assertSame('Chlamydomonas reinhardtii Nucleotide DB', $nucleotide_databases[123450]);

    // Test retrieval of protein db using long param.
    $protein_databases = $service->getDatabaseByType('protein');
    $this->assertArrayHasKey(67890, $protein_databases);
    $this->assertSame('Chlamydomonas reinhardtii Protein DB', $protein_databases[67890]);
  }

  /**
   * Tests retrieval of BLAST databases by identifier and config values.
   */
  public function testGetDatabaseByIdentifierAndConfig() {
    $this->createSampleDatabases();

    $service = \Drupal::service('tripal_blast.database_service');
    $this->assertInstanceOf(TripalBlastDatabaseService::class, $service);

    $expectations = self::$testdb_details[0];

    // By ID.
    $databases = $service->getDatabaseByIdentifier(['id' => $expectations['id']]);
    $this->assertIsArray($databases);
    $this->assertArrayHasKey($expectations['id'], $databases);

    // By Name.
    $databases = $service->getDatabaseByIdentifier(['name' => $expectations['name']]);
    $this->assertIsArray($databases);
    $this->assertArrayHasKey($expectations['id'], $databases);

    // By Path.
    $databases = $service->getDatabaseByIdentifier(['path' => $expectations['path']]);
    $this->assertIsArray($databases);
    $this->assertArrayHasKey($expectations['id'], $databases);

    // By Config.
    $config = $service->getDatabaseConfig($expectations['id']);
    $this->assertSame($expectations['id'], $config['id']);
    $this->assertSame($expectations['path'], $config['path']);
    $this->assertSame($expectations['dbtype'], $config['dbtype']);
  }

  /**
   * Tests program name translation based on database type and program.
   */
  public function testGetProgramName() {

    $service = \Drupal::service('tripal_blast.database_service');
    $this->assertInstanceOf(TripalBlastDatabaseService::class, $service);

    $expectations = [
      'n' => [
        'n' => 'blastn',
        'p' => 'blastx'
      ],
      'p' => [
        'n' => 'tblastn',
        'p' => 'blastp'
      ]
    ];

    // Check we get the expected program names for each combination of
    // database type and query type.
    // -- short name both database and query type.
    foreach ($expectations as $db_type => $query_types) {
      foreach ($query_types as $query_type => $expected_program) {
        $this->assertSame($expected_program, $service->getProgramName($db_type, $query_type));
      }
    }
    // -- long name both database and query type.
    $long_names = [
      'n' => 'nucleotide',
      'p' => 'protein'
    ];
    foreach ($expectations as $db_type => $query_types) {
      foreach ($query_types as $query_type => $expected_program) {
        $this->assertSame($expected_program, $service->getProgramName($long_names[$db_type], $long_names[$query_type]));
      }
    }
    // -- long name for database type and short name for query type.
    foreach ($expectations as $db_type => $query_types) {
      foreach ($query_types as $query_type => $expected_program) {
        $this->assertSame($expected_program, $service->getProgramName($long_names[$db_type], $query_type));
      }
    }
    // -- short name for database type and long name for query type.
    foreach ($expectations as $db_type => $query_types) {
      foreach ($query_types as $query_type => $expected_program) {
        $this->assertSame($expected_program, $service->getProgramName($db_type, $long_names[$query_type]));
      }
    }
  }

}
