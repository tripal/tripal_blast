<?php

namespace Drupal\Tests\tripal_blast\Kernel;

use Drupal\file\Entity\File;
use Drupal\Tests\tripal\Kernel\TripalTestKernelBase;
use Drupal\Tests\tripal_blast\Traits\TripalBlastTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\tripal\Services\TripalJob;
use Drupal\tripal\Services\TripalLogger;
use Drupal\tripal_blast\Services\TripalBlastJobService;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Tripal BLAST job service.
 */
#[Group('tripal-blast')]
#[RunTestsInSeparateProcesses]
class TripalBlastJobServiceTest extends TripalTestKernelBase {

  use TripalBlastTestTrait;
  use UserCreationTrait;

  /**
   * The path to tripal_blast module.
   *
   * @var string
   */
  private $module_path;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'file', 'tripal', 'tripal_blast'];

  /**
   * The YAML file indicating the scenarios to test.
   *
   * @var string
   */
  protected string $yaml_info_file = __DIR__ . '/tripalBlastJobs.yml';

  /**
   * Describes the environment to setup for this test.
   *
   * @var array
   *   An array with the following keys:
   *   - chado_version: the version of chado to test under.
   */
  protected array $system_under_test;

  /**
   * Describes the scenarios to test.
   *
   * This will be used in combination with the data provider. It can't be
   * accessed directly in the dataProvider due to the way that PHPUnit is
   * setup.
   *
   * @var array
   *  A list of scenarios where each one has the following keys:
   *  - label: A human-readable label for the scenario to be used in assert
   *    messages.
   *  - description: A description of the scenario and what you are wanting to
   *    test. This will not be used in the test but is rather there to help
   *    people reading the YAML file and to make it easier to maintain.
   *  - tripal_cv_obo: The values to insert into this table that define
   *    the name of the ontology and location of the obo file.
   *  - expect: An array of table.column expected values to confirm that
   *    they have been imported.
   */
  protected array $scenarios;

  /**
   * The list of blast databases used for testing.
   *
   * @var TripalBlastDatabase[]
   *   An associative array where the key is the database id and value is the
   *   TripalBlastDatabase entity.
   */
  protected array $blast_databases = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::state()->set('is_a_test_environment', TRUE);
    $this->prepareEnvironment(['TripalImporter']);
    $this->installSchema('tripal_blast', ['blastjob']);

    // Get the info from the YAML file.
    // First retrieve info from the YAML file for this particular test.
    [$this->system_under_test, $this->scenarios] = $this->getTestInfoFromYaml($this->yaml_info_file);

    // Create the blast databases used for testing.
    foreach ($this->system_under_test['databases'] as $blastdb) {
      $database = $this->createBlastDatabase($blastdb);
      $this->blast_databases[$database->id()] = $database;
    }

    $user = $this->createUser(['access content']);
    $this->setCurrentUser($user);

    $this->module_path = $this->container->get('module_handler')
      ->getModule('tripal_blast')
      ->getPath();

    // We need to mock the logger to test the progress reporting.
    $mock_logger = $this->getMockBuilder(TripalLogger::class)
      ->onlyMethods(['error', 'warning'])
      ->getMock();

    $mock_logger->method('error')
      ->willReturnCallback(function ($message, $context, $options) {
        print str_replace(array_keys($context), $context, $message);
        return NULL;
      });
    $mock_logger->method('warning')
      ->willReturnCallback(function ($message, $context, $options) {
        print str_replace(array_keys($context), $context, $message);
        return NULL;
      });
    $this->container->set('tripal.logger', $mock_logger);
  }

  /**
   * Get the list of scenarios to test.
   *
   * @return array
   */
  public static function provideScenarios(): array {
    $scenarios = [];

    $scenarios[] = [
      0,
      'Simple blastn job',
    ];

    return $scenarios;
  }
  /**
   * Tests that a blastjob record is saved and returned by the service.
   *
   * @dataProvider provideScenarios
   */
  #[DataProvider('provideScenarios')]
  public function testJobsSaveAndJobsGetJobByJobId(int $current_scenario_key, string $current_scenario_label): void {
    $current_scenario = $this->scenarios[$current_scenario_key];
    $this->assertEquals($current_scenario_label, $current_scenario['label'], "The scenario label does not match the expected label.");

    $service = \Drupal::service('tripal_blast.job_service');
    $this->assertInstanceOf(TripalBlastJobService::class, $service);

    $job_id = $service->createBlastJob($current_scenario['job']);
    $this->assertIsNumeric($job_id, "The job_id returned from createBlastJob is not numeric.");

    $job = $service->jobsGetJobByJobId($job_id, ['skip_file_check' => TRUE]);
    $this->assertIsObject($job);
    // @todo implement a way to compare the job object with the expected values.
    // $this->assertBlastJobContains($current_scenario['expectations']['job'], $job, "The retrieved job does not match what we expected.");
  }

  /**
   * Tests encoding and decoding a blast job id secret.
   *
   * @dataProvider provideScenarios
   */
  #[DataProvider('provideScenarios')]
  public function testJobsBlastMakeSecretAndRevealSecret(int $current_scenario_key, string $current_scenario_label): void {
    $current_scenario = $this->scenarios[$current_scenario_key];
    $this->assertEquals($current_scenario_label, $current_scenario['label'], "The scenario label does not match the expected label.");

    $service = \Drupal::service('tripal_blast.job_service');
    $this->assertInstanceOf(TripalBlastJobService::class, $service);

    $job_id = $service->createBlastJob($current_scenario['job']);
    $this->assertIsNumeric($job_id, "The job_id returned from createBlastJob is not numeric.");

    $secret = $service->jobsBlastMakeSecret($job_id);
    $this->assertNotSame((string) $job_id, $secret);

    $revealed = $service->jobsBlastRevealSecret($secret);
    $this->assertSame((string) $job_id, $revealed);
  }

  /**
   * Tests that recent blast jobs are rendered into a form table.
   *
   * @dataProvider provideScenarios
   */
  #[DataProvider('provideScenarios')]
  public function testJobsCreateTableReturnsExpectedRows(int $current_scenario_key, string $current_scenario_label): void {
    $current_scenario = $this->scenarios[$current_scenario_key];
    $this->assertEquals($current_scenario_label, $current_scenario['label'], "The scenario label does not match the expected label.");

    $service = \Drupal::service('tripal_blast.job_service');
    $this->assertInstanceOf(TripalBlastJobService::class, $service);

    // Let's get the recent jobs and confirm that there are none.
    $recent_jobs = $service->jobsGetRecentJobs([$current_scenario['job']['blast_program']]);
    $this->assertIsArray($recent_jobs, "jobsGetRecentJobs should always return an array.");
    $this->assertCount(0, $recent_jobs, "The recent jobs array is not empty when it should be.");
    // Also check the count.
    $recent_jobs_count = $service->jobsCountRecentJobs();
    $this->assertSame(0, $recent_jobs_count, "The recent jobs count is not zero when it should be.");

    // Now we create the job.
    $job_id = $service->createBlastJob($current_scenario['job']);
    $this->assertIsNumeric($job_id, "The job_id returned from createBlastJob is not numeric.");

    // Test that we can create the display table for the recent jobs.
    // We need to set the session variable that the service uses to determine
    // which jobs to display.
    $secret = $service->jobsBlastMakeSecret($job_id);
    $previous_session = $_SESSION['blast_jobs'] ?? NULL;
    $_SESSION['blast_jobs'] = [$secret];

    // Lets retrieve the recent jobs and confirm that the job we just created
    // is in the list.
    $recent_jobs = $service->jobsGetRecentJobs([$current_scenario['job']['blast_program']]);
    $this->assertIsArray($recent_jobs, "The recent jobs returned from jobsGetRecentJobs is not an array.");
    $this->assertCount(1, $recent_jobs, "The recent jobs array does not contain the expected number of jobs.");
    $this->assertArrayHasKey($job_id, $recent_jobs, "The recent jobs array does not contain the job_id of the job we just created.");
    // Also check the count.
    $recent_jobs_count = $service->jobsCountRecentJobs();
    $this->assertSame(1, $recent_jobs_count, "The recent jobs count is not one when it should be.");

    try {
      $jobs_table = $service->jobsCreateTable([ $current_scenario['job']['blast_program'] ]);
      $this->assertSame('Recent Jobs', $jobs_table['#title']);
      $this->assertCount(1, $jobs_table['#rows']);

      $expectations = $current_scenario['expectations'];
      $row = $jobs_table['#rows'][0];
      $this->assertStringContainsString($expectations['query']['name'], (string) $row[0]);
      $this->assertSame($expectations['job']['blastdb']['db_name'], $row[1]);
      $this->assertStringContainsString('See Results', (string) $row[3]);
    }
    finally {
      if ($previous_session === NULL) {
        unset($_SESSION['blast_jobs']);
      }
      else {
        $_SESSION['blast_jobs'] = $previous_session;
      }
    }
  }

  /**
   * Tests the successful cases of runJob() method.
   *
   * @return void
   */
  public function testValidRunJob(): void {
    $fixture_dir = $this->module_path . '/tests/fixtures/Chlamydomonas_reinhardtii_v5.6';

    $query_file = $fixture_dir . '/Chlamydomonas_gene.fasta';
    $database_prefix = $fixture_dir . '/Chlamydomonas_reinhardtii_v5.6';

    $temp_dir = sys_get_temp_dir() . '/tripal_blast_runjob_' . uniqid();
    mkdir($temp_dir, 0755, TRUE);

    $config = \Drupal::configFactory()->getEditable('tripal_blast.settings');
    $config->set('tripal_blast_config_general.path', '/usr/local/bin/')
      ->set('tripal_blast_config_general.threads', 1)
      ->save();

    $blast_path = \Drupal::config('tripal_blast.settings')
      ->get('tripal_blast_config_general.path');

    $output_stub = $temp_dir . '/tripal_blast_test_job';
    ob_start();
    $result = TripalBlastJobService::runJob('blastn', $query_file, $database_prefix, $output_stub, ['evalue' => '1e-5']);
    ob_end_clean();

    $this->assertNull($result, 'runJob should complete successfully and return null.');
    $this->assertFileExists($output_stub . '.asn');
    $this->assertFileExists($output_stub . '.xml');
    $this->assertFileExists($output_stub . '.tsv');
    $this->assertFileExists($output_stub . '.gff');
    $this->assertFileExists($output_stub . '.html');
  }

  /**
   * Tests the runJob() error messages when different file formats are missing.
   */
  public function testRunJobHandlesMissingFormatterOutputs(): void {
    $fixture_dir = $this->module_path . '/tests/fixtures/Chlamydomonas_reinhardtii_v5.6';

    $query_file = $fixture_dir . '/Chlamydomonas_gene.fasta';
    $database_prefix = $fixture_dir . '/Chlamydomonas_reinhardtii_v5.6';

    $temp_dir = sys_get_temp_dir() . '/tripal_blast_runjob_missing_' . uniqid();
    mkdir($temp_dir, 0755, TRUE);

    $output_stub = $temp_dir . '/tripal_blast_test_job';
    touch($output_stub . '.asn');

    $mock_job_service = $this->getMockBuilder(TripalBlastJobService::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getBlastCommand', 'jobsConvertTSVtoGFF3'])
      ->getMock();
    $mock_job_service->method('getBlastCommand')
      ->willReturn(['/bin/true', '/bin/true']);
    $mock_job_service->expects($this->once())
      ->method('jobsConvertTSVtoGFF3')
      ->willReturn(NULL);

    $this->container->set('tripal_blast.job_service', $mock_job_service);

    ob_start();
    $result = TripalBlastJobService::runJob('blastn', $query_file, $database_prefix, $output_stub, ['evalue' => '1e-5']);
    $printed_output = ob_get_contents();
    ob_end_clean();

    $this->assertNull($result, 'runJob should complete and return null when formatter outputs are missing.');
    $this->assertFileExists($output_stub . '.asn', 'We expected an .asn file to be present, but it is missing.');
    $this->assertStringContainsString('Unable to convert BLAST ASN.1 archive to XML', $printed_output, 'The expected logger error did not occur when generating the XML file.');
    $this->assertStringContainsString('Unable to convert BLAST ASN.1 archive to Tabular Output', $printed_output, 'The expected logger error did not occur when generating the tabular file.');
    $this->assertStringContainsString('Unable to convert BLAST Tabular Output to GFF Output', $printed_output, 'The expected logger error did not occur when generating the GFF file.');
    $this->assertStringContainsString('Unable to convert BLAST ASN.1 archive to HTML Output', $printed_output, 'The expected logger error did not occur when generating the HTML file.');
  }

  /**
   * Tests runJob() method's missing archive error message.
   *
   * @return void
   */
  public function testRunJobHandlesMissingArchiveOutput(): void {
    $fixture_dir = $this->module_path . '/tests/fixtures/Chlamydomonas_reinhardtii_v5.6';

    $query_file = $fixture_dir . '/Chlamydomonas_gene.fasta';
    $database_prefix = $fixture_dir . '/Chlamydomonas_reinhardtii_v5.6';

    $temp_dir = sys_get_temp_dir() . '/tripal_blast_runjob_noarchive_' . uniqid();
    mkdir($temp_dir, 0755, TRUE);

    $output_stub = $temp_dir . '/tripal_blast_test_job';

    $mock_job_service = $this->getMockBuilder(TripalBlastJobService::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getBlastCommand', 'jobsConvertTSVtoGFF3'])
      ->getMock();
    $mock_job_service->method('getBlastCommand')
      ->willReturn(['/bin/true', '/bin/true']);
    $mock_job_service->expects($this->never())
      ->method('jobsConvertTSVtoGFF3');

    $this->container->set('tripal_blast.job_service', $mock_job_service);

    ob_start();
    $result = TripalBlastJobService::runJob('blastn', $query_file, $database_prefix, $output_stub, ['evalue' => '1e-5']);
    $printed_output = ob_get_contents();
    ob_end_clean();

    $this->assertFalse($result, 'runJob should return FALSE when the BLAST archive file is not produced.');
    $this->assertFalse(file_exists($output_stub . '.asn'), 'The archive file should not exist.');
    $this->assertStringContainsString('BLAST did not complete successfully as is implied by the lack of output file', $printed_output, 'The expected error message for missing archive was not logged.');
  }

  /**
   * Tests the logger error in rubJob related to BLAST command generation.
   *
   * @return void
   */
  public function testRunJobErrors() {
    $fixture_dir = $this->module_path . '/tests/fixtures/Chlamydomonas_reinhardtii_v5.6';

    $query_file = $fixture_dir . '/Chlamydomonas_gene.fasta';
    $database_prefix = $fixture_dir . '/Chlamydomonas_reinhardtii_v5.6';

    $temp_dir = sys_get_temp_dir() . '/tripal_blast_runjob_' . uniqid();
    mkdir($temp_dir, 0755, TRUE);

    $config = \Drupal::configFactory()->getEditable('tripal_blast.settings');
    $config->set('tripal_blast_config_general.path', '/usr/local/bin/')
      ->set('tripal_blast_config_general.threads', 1)
      ->save();

    $blast_path = \Drupal::config('tripal_blast.settings')
      ->get('tripal_blast_config_general.path');

    $output_stub = $temp_dir . '/tripal_blast_test_job';

    ob_start();
    TripalBlastJobService::runJob('blastn', $query_file, 'tmp/usr/blastn', $output_stub, ['evalue' => '1e-5']);
    $printed_output = ob_get_contents();
    ob_end_clean();

    $this->assertStringContainsString(
      "Unable to generate the BLAST command for execution. The error was:",
      $printed_output,
      "The exception thrown does not have the message we expected when calling runJob() method."
    );
  }

  public static function provideDataForTestCreateBlastJobErrors() {
    return [
      'missing program' => [
        [
          'target_blastdb' => 123450,
          'target_file' => '/var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas_reinhardtii_v5.6.nsq',
          'query_file' => '/var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas_gene.fasta',
          'result_filestub' => '/var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas_reinhardtii_v5.6',
        ],
        [
          'expected_exception' => "Missing required parameter 'blast_program' when creating a new BLAST job.",
        ],
      ],
      'invalid program' => [
        [
          'blast_program' => 'tpblastn',
          'target_blastdb' => 123450,
          'target_file' => '/var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas_reinhardtii_v5.6.nsq',
          'query_file' => '/var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas_gene.fasta',
          'result_filestub' => '/var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas_reinhardtii_v5.6',
        ],
        [
          'expected_exception' => "Invalid value for parameter 'blast_program' when creating a new BLAST job. The value supplied was: tpblastn",
        ],
      ],
      'missing target database' => [
        [
          'blast_program' => 'blastn',
          'query_file' => '/var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas_gene.fasta',
          'result_filestub' => '/var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas_reinhardtii_v5.6',
        ],
        [
          'expected_exception' => "Missing required parameter 'target_blastdb' or 'target_file' when creating a new BLAST job.",
        ],
      ],
      'missing query file' => [
        [
          'blast_program' => 'blastn',
          'target_blastdb' => 123450,
          'target_file' => '/var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas_reinhardtii_v5.6.nsq',
          'result_filestub' => '/var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas_reinhardtii_v5.6',
        ],
        [
          'expected_exception' => "Missing required parameter 'query_file' when creating a new BLAST job."
        ],
      ],
      'query file does not exist' => [
        [
          'blast_program' => 'blastn',
          'target_blastdb' => 123450,
          'target_file' => '/var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas_reinhardtii_v5.6.nsq',
          'query_file' => '/var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas.fasta',
          'result_filestub' => '/var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas_reinhardtii_v5.6',
        ],
        [
          'expected_exception' => "The value supplied for parameter 'query_file' does not exist. The value supplied was: /var/www/drupal/web/modules/contrib/tripal_blast/tests/fixtures/Chlamydomonas_reinhardtii_v5.6/Chlamydomonas.fasta"
        ],
      ],
    ];
  }

  #[DataProvider('provideDataForTestCreateBlastJobErrors')]
  public function testCreateBlastJobErrors(array $job_parameters, array $results) {
    $service = \Drupal::service('tripal_blast.job_service');
    $this->assertInstanceOf(TripalBlastJobService::class, $service);
    $expection_message = 'NONE';
    $exception_caught = FALSE;

    try {
      $blast_job = $service->createBlastJob($job_parameters);
    }
    catch (\Exception $e) {
      $exception_caught = TRUE;
      $expection_message = $e->getMessage();
    }

    $this->assertTrue($exception_caught, "We expected an exception message when trying to create a blast job.");
    $this->assertSame(
      $results['expected_exception'],
      $expection_message,
      "We expected the excpetion to be {$results['expected_exception']}, but it was $expection_message."
    );

  }

}
