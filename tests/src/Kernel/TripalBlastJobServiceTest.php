<?php

namespace Drupal\Tests\tripal_blast\Kernel;

use Drupal\Tests\tripal\Kernel\TripalTestKernelBase;
use Drupal\Tests\tripal_blast\Traits\TripalBlastTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\tripal\Services\TripalJob;
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

}
