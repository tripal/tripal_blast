<?php
/**
 * @file
 * Contains class definition of Tripal BLAST Jobs service.
 */
namespace Drupal\tripal_blast\Services;

use Drupal\Core\Render\Markup;
use Drupal\tripal\Services\TripalJob;

class TripalBlastJobService {
  /**
   * Retrieve the number or recent jobs.
   */
  public function jobsCountRecentJobs() {
    return (isset($_SESSION['blast_jobs'])) ? sizeof($_SESSION['blast_jobs']) : 0;
  }

  /**
   * Return a list of recent blast jobs to be displayed to the user.
   *
   * @param $programs
   *   An array of blast programs you want jobs to be displayed for (ie: blastn, blastx, tblastn, blastp)
   *
   * @return
   *   An array of recent jobs.
   */
  public function jobsGetRecentJobs($programs = []) {
    $filter_jobs = !empty($programs);

    // Retrieve any recent jobs from the session variable.
    if (isset($_SESSION['blast_jobs'])) {

      $jobs = [];
      foreach ($_SESSION['blast_jobs'] as $job_secret) {
        $add = TRUE;

        $job_id = self::jobsBlastRevealSecret($job_secret);
        if ($job = self::jobsGetJobByJobId($job_id)) {

          // @TODO: Check that the results are still available.
          // This is meant to replace the arbitrary only show jobs executed less than 48 hrs ago.

          // Remove jobs from the list that are not of the correct program.
          if ($filter_jobs AND !in_array($job->program, $programs)) {
            $add = FALSE;
          }

          if ($add) {
            $job->query_summary = self::jobsFormatQueryHeaders($job->files->query);
            $jobs[] = $job;
          }
        }
      }

      return $jobs;
    }
    else {
      return [];
    }  
  }

  /**
   * Create a table listing recent BLAST jobs.
   * 
   * @param $program
   *   An array of program to filter from the jobs history.
   * 
   * @return array
   *   Form API table type.
   */
  public function jobsCreateTable($program = []) {
    $jobs = self::jobsGetRecentJobs($program);
    $jobs_table = [];
    
    $headers = ['Query Information', 'Search Target', 'Date Requested', '-'];
    
    foreach($jobs as $job) {
      $result_link = 'blast/report/' . self::jobsBlastMakeSecret($job->job_id);

      $rows[] = [
        $job->query_summary, 
        $job->blastdb->db_name, 
        $job->date_submitted,
        Markup::create('<a href="' . $result_link . '">See Results</a>')
      ];
    }

    $jobs_table = [
      '#type' => 'table',
      '#title' => 'Recent Jobs',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => t('0 Recent Jobs'),
    ];

    return $jobs_table;
  }

  /**
   * Makes the tripal job_id unrecognizable.
   *
   * @param $job_id
   *   The tripal job_id of the blast you want to make secret.
   *
   * @return
   *   A short string representing the job_id.
   */
  public function jobsBlastMakeSecret($job_id) {
    $mapping = self::jobsBlastMapSecret();
    $secret = str_replace(array_keys($mapping), $mapping, $job_id);

    return $secret;
  }

  /**
   * Reveals the true job_id for your secret blast result.
   *
   * @param $secret
   *    The job_id previously made secret by blast_ui_make_secret().
   *
   * @return
   *    The revealed tripal job_id.
   */
  public function jobsBlastRevealSecret($secret) {
    $mapping = self::jobsBlastMapSecret(TRUE);
    $job_id = str_replace(array_keys($mapping), $mapping, $secret);

    if (is_numeric($job_id)) {
      // Check that the job_id exists if it is an integer.
      $exists = self::jobsGetJobByJobId($job_id);

      if ($exists) {
        return $job_id;
      }
      else {
        tripal_report_error(
          'blast_ui',
          TRIPAL_ERROR,
          'Unable to decode the blast job_id from :id.',
          [':id' => $secret]
        );
      }
    }
    else {
      // Last ditch effort: maybe this job was encoded before the upgrade?
      $job_id = base64_decode($secret);

      if (is_numeric($job_id)) {
        $exists = self::jobsGetJobByJobId($job_id);

        if ($exists) {
          return $job_id;
        }
        else {
          tripal_report_error(
            'blast_ui',
            TRIPAL_ERROR,
            'Unable to decode the blast job_id from :id.',
            [':id' => $secret]
          );
        }
      }
      else {
        tripal_report_error(
          'blast_ui',
          TRIPAL_ERROR,
          'Unable to decode the blast job_id from :id.',
          array(':id' => $secret)
        );
      }
    }

    return FALSE;
  }

  /**
   * A single location for keeping track of the mapping used in our secrets.
   */
  public function jobsBlastMapSecret($reveal = FALSE) {
    $mapping = [
      1 => 'P',
      2 => 'sA',
      3 => 'b',
      4 => 'Q',
      5 => 'Hi',
      6 => 'yt',
      7 => 'f',
      8 => 'zE',
      9 => 'Km',
      0 => 'jVo',
    ];

    // Since this is an open-source module with all the code publically available,
    // our secret is not very secret... We are ok with this since the liklihood of
    // profiting by stealing random blast results is pretty low. That said, if this bothers
    // you, feel free to implement the following function in a private module to change
    // this mapping to something that cannot easily be looked up on github. ;-).
    // NOTE: Ensure that the mapping you come up with is unique to ensure that the
    // job_id can be consistently revealed or your users might end up unable to find
    // their own blast results...
    if (function_exists('private_make_mapping_ultra_secret')) {
      $mapping = private_make_mapping_ultra_secret($mapping);
    }

    if ($reveal) {
      return array_flip($mapping);
    }
    else {
      return $mapping;
    }
  }

  /**
   * Get BLAST job by job_id.
   * 
   * @param $job_id
   *   Unique id of BLAST job request.
   * 
   * @retrun object
   *   Job record matching the job id.
   */
  public function jobsGetJobByJobId($job_id) {
    $query = \Drupal::database()->select('blastjob', 'jobs');
    $query->fields('jobs');
    $query->condition('jobs.job_id', $job_id);
    
    $blastjob = $query->execute()->fetchObject();
    if (!$blastjob) {
      return FALSE;
    }
    
    $tripal_job = new TripalJob;
    $tripal_job = $tripal_job->load($job_id);
    $job = new \stdClass();
    $job->job_id = $job_id;
    $job->program = $blastjob->blast_program;
    $job->options = unserialize($blastjob->options);
    $job->date_submitted = $tripal_job->submit_date;
    $job->date_started = $tripal_job->start_time;
    $job->date_completed = $tripal_job->end_time;

    // TARGET BLAST DATABASE.
    if ($blastjob->target_blastdb ) {
      // If a provided blast database was used then load details.
      $job->blastdb = \Drupal::service('tripal_blast.database_service')
        ->getDatabaseByIdentifier(['id' => $blastjob->target_blastdb]);
    }    
    else {
      // Otherwise the user uploaded their own database so provide what information we can.
      $job->blastdb = new \stdClass();
      $job->blastdb->db_name = 'User Uploaded';
      $job->blastdb->db_path = $blastjob->target_file;
      $job->blastdb->linkout = new \stdClass();
      $job->blastdb->linkout->none = TRUE;

      if ($job->program == 'blastp' OR $job->program == 'tblastn') {
        $job->blastdb->db_dbtype = 'protein';
      }
      else {
        $job->blastdb->db_dbtype = 'nucleotide';
      }
    }

    // FILES.
    $job->files = new \stdClass();
    $job->files->query = $blastjob->query_file;
    $job->files->target = $blastjob->target_file;
    $job->files->result = new \stdClass();
    $job->files->result->archive = $blastjob->result_filestub . '.asn';
    $job->files->result->xml = $blastjob->result_filestub . '.xml';
    $job->files->result->tsv = $blastjob->result_filestub . '.tsv';
    $job->files->result->html = $blastjob->result_filestub . '.html';
    $job->files->result->gff = $blastjob->result_filestub . '.gff';

    return $job;
  }

  /**
   * Save job information/parameters into blastjob table.
   * 
   * @param $job_parameters
   *   Parameters used to execute the job.
   */
  public function jobsSave($job_parameters) {
    \Drupal::service('database')->insert('blastjob')
      ->fields([
        'job_id' => $job_parameters['job_id'],
        'blast_program' => $job_parameters['blast_program'],
        'target_blastdb' => $job_parameters['target_blastdb'],
        'target_file' => $job_parameters['target_file'],
        'query_file' => $job_parameters['query_file'],
        'result_filestub' => $job_parameters['result_filestub'],
        'options' => $job_parameters['options']
      ])
      ->execute();
  }

  /**
   * Summarize a fasta file based on it's headers.
   *
   * @param $file
   *   The full path to the FASTA file.
   *
   * @return
   *   A string describing the number of sequences and often including the first query header.
   */
  public function jobsFormatQueryHeaders($file) {
    $headers = [];
    exec('grep ">" ' . escapeshellarg($file), $headers);

    // Easiest case: if there is only one query header then show it.
    if (sizeof($headers) == 1 AND isset($headers[0])) {
      return ltrim($headers[0], '>');
    }
    // If we have at least one header then show that along with the count of queries.
    elseif (isset($headers[0])) {
      return sizeof($headers) . ' queries including "' . ltrim($headers[0], '>') . '"';
    }
    // If they provided a sequence with no header.
    elseif (empty($headers)) {
      return 'Unnamed Query';
    }
    // At the very least show the count of queries.
    else {
      return sizeof($headers) . ' queries';
    }
  }

}