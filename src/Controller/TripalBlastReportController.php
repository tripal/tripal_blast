<?php
/**
 * @file 
 * This is the controller for Tripal BLAST help page.
 */

namespace Drupal\tripal_blast\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\tripal\Services\TripalJob;

/**
 * Defines TripalBlastReportController class.
 * 
 */
class TripalBlastReportController extends ControllerBase {  
  public function report($job_id) {
    // BLASTs are run as a Tripal job. As such we need to determine whether the current
    // BLAST is in the queue, running or complete in order to determine what to show the user
    // decode the job_id
    $job_service = \Drupal::service('tripal_blast.job_service');
    $job_id = $job_service->jobsBlastRevealSecret($job_id);
    
    $tripaljob = new TripalJob;
    $tripaljob->load($job_id);
    $job = $tripaljob->getJob();

    if ($job->start_time == NULL AND $job->end_time == NULL) {
      // 1) Job is in the Queue.
      $theme = 'theme-tripal-blast-report-pending';
      $job_param = [
        'job_id' => '',
        'status' => 'Pending',        
        'status_code' => 0        
      ];
    }
    elseif (strtolower($job->status) == 'cancelled') {
      // 2) Job has been Cancelled.
      $theme = 'theme-tripal-blast-report-pending';
      $job_param = [
        'job_id' => '',
        'status' => 'Cancelled',        
        'status_code' => 999        
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
      $theme = 'theme-tripal-blast-report_pending';
      $job_param = [
        'job_id' => '',
        'status' => 1,        
        'status_code' => 'Running'        
      ];
    }

    return [
      '#theme' => $theme,
      '#attached' => [
        'library' => ['tripal_blast/tripal-blast-report']
      ],
      '#report' => $report
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
    // Get job profile.
    $job_service = \Drupal::service('tripal_blast.job_service');
    $blast_job = $job_service->jobsGetJobByJobId($job_id);
     
    // Add to markup.
    $blast_job->blast_cmd = $blast_job->program;
    foreach($blast_job->options as $key => $value) {
      $blast_job->blast_cmd .= ' -' . $key . ' ' . $value;
    }

    // CVITJS
    $blast_job->show_civitjs_diagram = FALSE;
    $config_cvitjs_enabled = \Drupal::config('tripal_blast.settings')
      ->get('tripal_blast_config_visualization.cvitjs_enabled');
    if ($config_cvitjs_enabled
        && $blast_job->blastdb->cvitjs_enabled
        && $blast_job->blastdb->cvitjs_enabled == '1') {
        
      $blast_job->show_civitjs_diagram = TRUE;
      
      // Add to libraries.
      $blast_job->library = 'tripal-blast/tripal-blast-cvitjs';
      $blast_job->settings = [
        'dataset' => $blast_job->blastdb->db_name,
        'gff' => $base_path . '/' . $blast_job->files->result->gff
      ];
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
        $blast_job->blast_form_url = \Drupal::l($this->t($name), $link);
        
        break;
      }
    } 
    
    // Load the XML file.
    // Add to markup.
    $blast_job->xml = TRUE; //NULL; @TODO change value.
    $blast_job->num_results = FALSE;
    $blast_job->too_many_results = FALSE;

    $full_path_xml = DRUPAL_ROOT . DIRECTORY_SEPARATOR . $blast_job->files->result->xml;
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

    $blast_job->hola = '<h1>Test Result</h1>';
    return $blast_job;
  }
}