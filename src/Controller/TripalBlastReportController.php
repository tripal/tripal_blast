<?php
namespace Drupal\tripal_blast\Controller;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\tripal\Services\TripalJob;

/**
 * Defines TripalBlastReportController class.
 */
class TripalBlastReportController extends ControllerBase {
  public function report($job_id) {
    $report = NULL;

    // BLASTs are run as a Tripal job. As such we need to determine whether the current
    // BLAST is in the queue, running or complete in order to determine what to show the user
    // decode the job_id
    $job_service = \Drupal::service('tripal_blast.job_service');
    $job_id = $job_service->jobsBlastRevealSecret($job_id);

    $tripaljob = new TripalJob;
    $tripaljob->load($job_id);
    $job = $tripaljob->getJob();

    if ($job->start_time == NULL AND $job->end_time == NULL) {
      $this->messenger()->addMessage($this->t('Your BLAST job is in the queue and will be processed shortly. Please remain on this page to see your results.'));
      // 1) Job is in the Queue.
      $theme = 'theme-tripal-blast-report-pending';
      $job_param = [
        'job_id' => '',
        'status' => 'Pending',
        'status_code' => 0
      ];
    }
    elseif (strtolower($job->status) == 'cancelled') {
      $this->messenger()->addWarning($this->t('Your BLAST job has been cancelled by an administrator.'));
      // 2) Job has been Cancelled.
      $theme = 'theme-tripal-blast-report-pending';
      $job_param = [
        'job_id' => '',
        'status' => 'Cancelled',
        'status_code' => 999
      ];
    }
    elseif (strtolower($job->status) == 'error') {
      $this->messenger()->addError($this->t('Your BLAST job has encountered an error.'));
      // 2) Job has encountered an error.
      $theme = 'theme-tripal-blast-report-pending';
      $job_param = [
        'job_id' => '',
        'status' => 'Error',
        'status_code' => 666
      ];
    }
    elseif ($job->end_time !== NULL) {
      // 3) Job is Complete
      $theme = 'theme-tripal-blast-show-report';
      $job_param = [
        'job_id' => $job_id,
        'status' => '',
        'status_code' => ''
      ];

      $report = $this->prepareReport($job_id);
    }
    else {
      // 4) Job is in Progress
      $theme = 'theme-tripal-blast-report-pending';
      $job_param = [
        'job_id' => '',
        'status' => 'Running',
        'status_code' => 1
      ];
    }

    return [
      '#theme' => $theme,
      '#attached' => [
        'library' => ['tripal_blast/tripal-blast-report']
      ],
      '#report' => $report,
      '#job' => $job_param,
    ];
  }

  /**
   * Prepare report page.
   *
   * @param $job_id
   *   Job id the report is based on.
   *
   * @return string
   *   Report page markup.
   */
  public function prepareReport($job_id) {
    $logger = \Drupal::logger('tripal_blast');

    // Get job profile.
    $job_service = \Drupal::service('tripal_blast.job_service');
    $blast_job = $job_service->jobsGetJobByJobId($job_id, ['skip_file_check' => TRUE]);

    // Add to markup.
    $output_files = array_map(fn($item) => $item['absolute_path'], $blast_job->files->result);
    try {
      $job_service = \Drupal::service('tripal_blast.job_service');
      $blast_job->blast_cmd = $job_service->getBlastCommand(
        $blast_job->program,
        $blast_job->files->query,
        $blast_job->files->target,
        $output_files,
        $blast_job->options
      )[0];
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Unable to generate the BLAST command for execution. Please contact the site administrator.'));
      $logger->error("Unable to generate the BLAST command for execution for job ID @job_id. The error was: @error", ['@job_id' => $job_id, '@error' => $e->getMessage()]);
    }

    // Determine the URL of the blast form
    $blast_programs = [
      'blastn'  => ['nucleotide', 'nucleotide'],
      'blastx'  => ['nucleotide', 'protein'],
      'tblastn' => ['protein', 'nucleotide'],
      'blastp'  => ['protein', 'protein']
    ];
    $route_ui = 'tripal_blast.blast_program';

    foreach($blast_programs as $name => $param) {
      if ($name == $blast_job->program) {
        list($query, $db) = $param;
        $link = Url::fromRoute($route_ui, ['query' => $query, 'db' => $db]);
        // Add to markup.
        $blast_job->blast_form_url = Link::fromTextAndUrl($name, $link)->toString();

        break;
      }
    }

    // Load the XML file.
    // Add to markup.
    $blast_job->xml = TRUE; //NULL; @TODO change value.
    $blast_job->num_results = FALSE;
    $blast_job->too_many_results = FALSE;

    $full_path_xml = $blast_job->files->result['xml']['absolute_path'];
    if (is_readable($full_path_xml)) {
      $blast_job->num_results = shell_exec('grep -c "<Hit>" ' . escapeshellarg($full_path_xml));

      $max_results = \Drupal::config('tripal_blast.settings')
        ->get('tripal_blast_config_jobs.max_result');

      if ($blast_job->num_results < $max_results) {
        $blast_job->xml = simplexml_load_file($full_path_xml);
      }
      else {
        $blast_job->too_many_results = TRUE;
      }
    }

    $blast_job->num_results_formatted = number_format(floatval($blast_job->num_results));

    $blast_job->linkout = FALSE;
    if ($blast_job->blastdb->linkout->none === FALSE) {
      $blast_job->linkout_type  = $blast_job->blastdb->linkout->type;
      $blast_job->linkout_regex = $blast_job->blastdb->linkout->regex;

      // Note that URL prefix is not required if linkout type is 'custom'
      if (isset($blast_job->blastdb->linkout->db_id->urlprefix) && !empty($blast_job->blastdb->linkout->db_id->urlprefix)) {
        $blast_job->linkout_urlprefix = $blast_job->blastdb->linkout->db_id->urlprefix;
      }

      // Check that we can determine the linkout URL.
      // (ie: that the function specified to do so, exists).
      if (function_exists($blast_job->blastdb->linkout->url_function)) {
        $blast_job->url_function = $blast_job->blastdb->linkout->url_function;
        $blast_job->linkout = TRUE;
      }
    }

    $blast_job->submission_date = \Drupal::service('date.formatter')
      ->format($blast_job->date_submitted, 'medium');


    // Handle no hits. This following array will hold the names of all query
    // sequences which didn't have any hits.
    $blast_job->query_with_no_hits = array();

    // Furthermore, if no query sequences have hits we don't want to bother listing
    // them all but just want to give a single, all-include "No Results" message.
    $blast_job->no_hits = TRUE;

    return $blast_job;
  }
}
