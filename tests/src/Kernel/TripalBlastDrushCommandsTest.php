<?php

namespace Drupal\Tests\tripal_blast\Kernel\Drush;

use Drupal\Core\File\FileSystemInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tripal_blast\Drush\Commands\TripalBlastCommands;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;

/**
 * Tests the drush commands in the tripal_blast module.
 *
 * @group TripalBlast
 * @group drush-command
 */
#[Group('tripal-blast')]
#[Group('drush-command')]
#[RunTestsInSeparateProcesses]
class TripalBlastDrushCommandsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'tripal',
    'tripal_chado',
    'tripal_blast',
  ];

  /**
   * Concatenated string to capture logged messages.
   *
   * @var string
   */
  protected string $log_output = '';

  /**
   * An object of a drush commands class.
   *
   * @var Drupal\tripal_chado\Drush\Commands\TripalBlastCommands
   */
  protected TripalBlastCommands $drush_command;

  /**
   * Setup test environment.
   */
  protected function setUp(): void {
    parent::setUp();

    // Initialize services.
    $drupal_connection = \Drupal::database();
    $file_system = \Drupal::service('file_system');

    $this->installSchema('tripal', ['tripal_jobs']);
    $this->installSchema('tripal_blast', ['blastjob']);
    $tb_dir = 'public://tripal_blast';
    $success = $file_system->prepareDirectory($tb_dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $this->assertTrue($success, 'Problem creating tripal_blast directory');

    // Set up a mocked logger to capture all messages.
    $mock_logger = $this->getMockBuilder(LoggerInterface::class)
      ->getMock();
    $mock_logger->method('notice')
      ->willReturnCallback(function ($message, $context = []) {
        $this->log_output .= 'NOTICE: ' . (string) $message . "\n";
        return NULL;
      }
    );
    $mock_logger->method('error')
      ->willReturnCallback(function ($message, $context = []) {
        $this->log_output .= 'ERROR: ' . (string) $message . "\n";
        return NULL;
      }
    );

    // An instance of the TripalBlastCommands drush command class.
    $this->drush_command = new TripalBlastCommands(
      $this->container->get('database'),
      $this->container->get('file_system'),
    );
    $this->drush_command->setLogger($mock_logger);

    // Set up some job table records.
    $now = time();
    $seven_days = 86400 * 7 + 1;
    $tripal_jobs = [
      [
        'job_id' => 1,
        'uid' => 1,
        'job_name' => 'job_finished',
        'modulename' => 'blast_job',
        'callback' => 'a:0:{}',
        'status' => 'Completed',
        'submit_date' => $now - $seven_days - 20,
        'start_time' => $now - $seven_days - 10,
        'end_time' => $now - $seven_days,
        'priority' => 0,
      ],
      [
        'job_id' => 2,
        'uid' => 2,
        'job_name' => 'job_running',
        'modulename' => 'blast_job',
        'callback' => 'a:0:{}',
        'status' => 'Running',
        'submit_date' => $now - 2000,
        'start_time' => $now - 1990,
        'priority' => 0,
      ],
      [
        'job_id' => 3,
        'uid' => 3,
        'job_name' => 'job_not_started',
        'modulename' => 'blast_job',
        'callback' => 'a:0:{}',
        'status' => 'Waiting',
        'submit_date' => $now - 1000,
        'priority' => 0,
      ],
    ];
    $blastjobs = [
      [
        'job_id' => 1,
        'uuid' => '1234b567-c85e-12f5-c325-718327814391',
        'blast_program' => 'blastn',
        'query_file' => '/tmp/tripalblastphpunittest_1.fna',
        'result_filestub' => 'public://tripal_blast/tripalblastphpunittest_1',
      ],
      [
        'job_id' => 2,
        'uuid' => '1234b567-c85e-12f5-c325-718327814392',
        'blast_program' => 'blastn',
        'query_file' => '/tmp/tripalblastphpunittest_2.fna',
        'result_filestub' => 'public://tripal_blast/tripalblastphpunittest_2',
      ],
      [
        'job_id' => 3,
        'uuid' => '1234b567-c85e-12f5-c325-718327814393',
        'blast_program' => 'blastn',
        'query_file' => '/tmp/tripalblastphpunittest_3.fna',
        'result_filestub' => 'public://tripal_blast/tripalblastphpunittest_3',
      ],
    ];
    foreach ($tripal_jobs as $job) {
      $query = $drupal_connection->insert('tripal_jobs');
      $query->fields($job);
      $id = $query->execute();
      $this->assertEquals($job['job_id'], $id, 'Inserted tripal_jobs id mismatch');
    }
    foreach ($blastjobs as $job) {
      $query = $drupal_connection->insert('blastjob');
      $query->fields($job);
      $query->execute();

      // Create placeholder input and output files.
      // Job 3 has not started, so no output files.
      $file_system->saveData('x', $job['query_file']);
      if ($job['job_id'] < 3) {
        $extensions = ['xml', 'asn', 'tsv', 'gff', 'html'];
        foreach ($extensions as $extension) {
          $file_system->saveData('x', $job['result_filestub'] . '.' . $extension);
        }
      }
    }
  }

  /**
   * Teardown test environment.
   */
  protected function tearDown(): void {
    // This modules saves the submitted blast query sequences to
    // the /tmp directory. We need to clean these up.
    // Output files were saved to public://tripal_blast/ and those
    // are removed for us automatically.
    $file_system = \Drupal::service('file_system');
    $files = $file_system->scanDirectory('/tmp', '/tripalblastphpunittest/');
    foreach ($files as $file) {
      $file_system->delete($file->uri);
    }
    parent::tearDown();
  }

  /**
   * Gets stored mocked log output and then resets it.
   *
   * @return string
   *   Concatenated log messages.
   */
  protected function getLogOutput(): string {
    $messages = $this->log_output;
    $this->log_output = '';
    return $messages;
  }

  /**
   * Tests drush command tripal-blast:clean (trp-blast-clean).
   */
  public function testCleanCommand(): void {

    // Test age validation.
    $options = [
      'age' => 'xyz',
    ];
    $this->drush_command->tripalBlastClean($options);
    $messages = $this->getLogOutput();
    $this->assertStringContainsString('The --age argument specified is not a valid number', $messages, 'Did not receive expected validation error');
    $options = [
      'age' => '-7',
    ];
    $this->drush_command->tripalBlastClean($options);
    $messages = $this->getLogOutput();
    $this->assertStringContainsString('The --age argument must be a non-negative number', $messages, 'Did not receive expected validation error');

    // Test with all jobs newer than age, nothing to remove.
    $options = [
      'age' => '100',
    ];
    $this->drush_command->tripalBlastClean($options);
    $messages = $this->getLogOutput();
    $this->assertStringContainsString('There are no blast jobs older than 100 days', $messages, 'Did not receive expected notice');

    // Test with all default parameters. Nothing will be removed.
    $options = [];
    $this->drush_command->tripalBlastClean($options);
    $messages = $this->getLogOutput();
    $this->assertStringContainsString('There are 1 blast jobs older than 7 days, having 6 output files', $messages, 'Did not receive expected notice');
    $this->assertStringContainsString('No parameters provided, no files or jobs will be removed', $messages, 'Did not receive expected notice');

    // Test with --file parameter. Oldest job files only are deleted.
    $options = [
      'files' => TRUE,
    ];
    $this->drush_command->tripalBlastClean($options);
    $messages = $this->getLogOutput();
    $this->assertStringContainsString('There are 1 blast jobs older than 7 days, having 6 output files', $messages, 'Did not receive expected notice');

    // Test again with --file parameter. Job files should have been deleted.
    $options = [
      'files' => TRUE,
    ];
    $this->drush_command->tripalBlastClean($options);
    $messages = $this->getLogOutput();
    $this->assertStringContainsString('There are 1 blast jobs older than 7 days, having 0 output files', $messages, 'Did not receive expected notice');

    // Now delete just the blast job.
    $options = [
      'blastjob' => TRUE,
    ];
    $this->drush_command->tripalBlastClean($options);
    $messages = $this->getLogOutput();
    $this->assertStringContainsString('There are 1 blast jobs older than 7 days, having 0 output files', $messages, 'Did not receive expected notice');
    $this->assertStringContainsString('Removing 1 blastjob table entries', $messages, 'Did not receive expected notice');

    // Now delete the tripal job.
    $options = [
      'tripaljob' => TRUE,
    ];
    $this->drush_command->tripalBlastClean($options);
    $messages = $this->getLogOutput();
    $this->assertStringContainsString('There are 1 blast jobs older than 7 days, having 0 output files', $messages, 'Did not receive expected notice');
    $this->assertStringContainsString('Removing 1 tripal_jobs table entries', $messages, 'Did not receive expected notice');

    // Try again there should be no old jobs now.
    $options = [
      'tripaljob' => TRUE,
    ];
    $this->drush_command->tripalBlastClean($options);
    $messages = $this->getLogOutput();
    $this->assertStringContainsString('There are no blast jobs older than 7 days', $messages, 'Did not receive expected notice');

    // Delete all remaining jobs. Test verbose mode.
    $options = [
      'age' => 0.01,
      'tripaljob' => TRUE,
      'verbose' => TRUE,
    ];
    $this->drush_command->tripalBlastClean($options);
    $messages = $this->getLogOutput();
    $expectations = [
      'There are 2 blast jobs older than 0.01 days, having 7 output files',
      'Removing file /tmp/tripalblastphpunittest_2.fna',
      'Removing file public://tripal_blast/tripalblastphpunittest_2.xml',
      'Removing file public://tripal_blast/tripalblastphpunittest_2.asn',
      'Removing file public://tripal_blast/tripalblastphpunittest_2.tsv',
      'Removing file public://tripal_blast/tripalblastphpunittest_2.gff',
      'Removing file public://tripal_blast/tripalblastphpunittest_2.html',
      'Removing file /tmp/tripalblastphpunittest_3.fna',
      'Removing 2 blastjob table entries',
      'Removing 2 tripal_jobs table entries',
    ];
    foreach ($expectations as $expectation) {
      $this->assertStringContainsString($expectation, $messages, 'Did not receive expected notice');
    }
  }

}
