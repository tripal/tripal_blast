<?php

namespace Drupal\tripal_blast\Drush\Commands;

use Drupal\pgsql\Driver\Database\pgsql\Connection;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * Drush commands.
 */
class TripalBlastCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * TripalBlastCommands Drush command class constructor.
   *
   * This is used to inject the services used by the various commands.
   */
  public function __construct(
    protected Connection $drupal_connection,
  ) {
    // Parent currently doesn't do anything here.
    parent::__construct();
  }

  /**
   * Expires old blast runs.
   *
   * @param array $options
   *   Options passed on the drush command line.
   *
   * @return void
   *   No return value.
   */
  #[CLI\Command(name: 'tripal-blast:clean', aliases: ['trp-blast-clean'])]
  #[CLI\Option(name: 'files', description: 'Remove old files (default)')]
  #[CLI\Option(name: 'blastjob', description: 'Remove old files and blastjob table entries.')]
  #[CLI\Option(name: 'tripaljob', description: 'Remove old files, blastjob, and tripal_jobs table entries.')]
  #[CLI\Option(name: 'age', description: 'Maximum age of files to retain in days. Default is 7 days.')]
  #[CLI\Usage(
    name: "drush tripal-blast:clean",
    description: 'Deletes old blast output files older than 7 days. Only files are removed, all job table entries are retained.',
  )]
  #[CLI\Usage(
    name: "drush trp-blast-clean --blastjob --age=10",
    description: 'Deletes old blast output files older than 10 days, and remove blastjob table entries, but retain tripal_jobs table entries.',
  )]
  public function tripalBlastClean(
    array $options = [
      'files' => TRUE,
      'blastjob' => FALSE,
      'tripaljob' => FALSE,
      'age' => '7',
    ]): void {

    // Define defaults here so that both drush and phpunit test
    // environments have the same defaults.
    $option_files = $options['files'] ?? TRUE;
    $option_blastjob = $options['blastjob'] ?? FALSE;
    $option_tripaljob = $options['tripaljob'] ?? FALSE;
    $option_age = $options['age'] ?? 7;
    if (!is_numeric($option_age)) {
      $this->logger->error($this->t('The --age argument specified is not a valid number.'));
      return;
    }
    if ($option_age < 0) {
      $this->logger->error($this->t('The --age argument must be a non-negative number.'));
      return;
    }

    if ($option_blastjob) {
      $option_files = TRUE;
    }
    if ($option_tripaljob) {
      $option_files = TRUE;
      $option_blastjob = TRUE;
    }

    // Query blast jobs with start times older than the specified age.
    $cutoff = intval(time() - ($option_age * 43200));
    $query = $this->drupal_connection->select('tripal_jobs', 'T');
    $query->leftJoin('blastjob', 'B', '[T].job_id = [B].job_id');
    $query->condition('T.modulename', 'blast_job', '=');
    $query->condition('T.start_time', $cutoff, '<=');
    $query->addField('B', 'query_file', 'query_file');
    $query->addField('B', 'result_filestub', 'result_filestub');
    $query->addField('T', 'job_id', 'job_id');
    $query->addField('T', 'job_name', 'job_name');
    $results = $query->execute()->fetchAll();
    $count = count($results);
    if ($count) {
      $files = [];
      $job_ids = [];
      foreach ($results as $result) {
        $job_ids[] = $result->job_id;
        // Query files may disappear after a reboot as they may be in /tmp/.
        if ($result->query_file && file_exists($result->query_file)) {
          $files[] = $result->query_file;
        }
        // As a safety precaution, the filestub should always include
        // "tripal_blast". If we previously deleted the blastjob entry
        // but not the tripal_jobs entry, it could be empty. Don't just
        // randomly glob if we don't have a valid stub.
        if ($result->result_filestub && preg_match('/\/tripal_blast\//', $result->result_filestub)) {
          $files += glob($result->result_filestub . '*', GLOB_NOSORT);
        }
      }
      if (!$options['silent']) {
        $this->logger->notice($this->t('Removing @count_jobs blast jobs older than @age days, having @count_files output files.',
          ['@count_jobs' => $count, '@age' => $option_age, '@count_files' => count($files)]));
      }

      // Delete the blast input and output files.
      foreach ($files as $file) {
        if ($options['verbose']) {
          $this->logger->notice($this->t('Removing file @file.', ['@file' => $file]));
        }
        if (!unlink($file) || file_exists($file)) {
          $this->logger->error($this->t('Failed to delete file @file.', ['@file' => $file]));
        }
      }

      // Delete the blastjob table entries.
      if ($option_blastjob && $job_ids) {
        $query = $this->drupal_connection->delete('blastjob');
        $query->condition('job_id', $job_ids, 'IN');
        $query->execute();
      }

      // Delete the tripal_jobs table entries.
      if ($option_tripaljob && $job_ids) {
        $query = $this->drupal_connection->delete('tripal_jobs');
        $query->condition('job_id', $job_ids, 'IN');
        $query->execute();
      }

    }
    else {
      if (!$options['silent']) {
        $this->logger->notice($this->t('There are no blast jobs older than @age days.',
          ['@age' => $option_age]));
      }
    }
  }

}
