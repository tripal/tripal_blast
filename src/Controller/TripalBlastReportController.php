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
      
      $blast_job = $job_service->jobsGetJobByJobId($job_id);
      $report_param = $this->getBlastReportItems($blast_job);
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
      // Tripal BLAST Help page theme.
      '#theme' => $theme,
      '#attached' => [
        'library' => [''] // @TODO: ADD CSS AND JS
      ],
      '#job_param' => $report_param
    ];  
  }

  /**
   * Prepare information relating to the BLAST Jobs.
   */
  public function getBlastReportItems($blast_job) {
    // Get blast job details.
    $blast_job->blast_cmd = $blast_job->program;
    // Determine the BLAST command for display.
    foreach($blast_job->options as $key => $value) {
      $blast_job->blast_cmd .= ' -' . $key . ' ' . $value;
    }

    // CViTjs
    $blast_job->show_cvit_diagram = FALSE;
    $config_cvitjs_enabled = \Drupal::config('tripal_blast.settings')
      ->get('tripal_blast_config_visualization.cvitjs_enabled');

    if ($config_cvitjs_enabled
         && isset($blast_job->blastdb->cvitjs_enabled) 
         && $blast_job->blastdb->cvitjs_enabled == '1') {
      
      $blast_job->show_cvit_diagram = TRUE;

      // @TODO: ADD CIVIT LIBRARY
    }

    // Determine the URL of the BLAST form.
    $blast_job->blast_form_url = 'blast/nucleotide/nucleotide';
    $blast_programs = [
      'blastn'  => ['nucleotide', 'nucleotide'],
      'blastx'  => ['nucleotide', 'protein'],
      'tblastn' => ['protein', 'nucleotide'],
      'blastp'  => ['protein', 'protein']
    ];
    $route_ui = 'tripal_blast.blast_program';
    $links_ui = [];
  
    foreach($blast_programs as $name => $param) {
      if ($name == $blast_job->program) {
        list($query, $db) = $param;
        
        $link = Url::fromRoute($route_ui, ['query' => $query, 'db' => $db]);
        $blast_job->blast_form_url = \Drupal::l(t($name), $link);
        break;
      }
    } 
   
    // Load the XML file.
    $blast_job->xml = NULL;
    $blast_job->num_results = FALSE;
    $blast_job->too_many_results = FALSE;

    $full_path_xml = DRUPAL_ROOT . DIRECTORY_SEPARATOR . $blast_job->files->result->xml;
    if (is_readable($full_path_xml)) {
      $blast_job->num_results = shell_exec('grep -c "<Hit>" ' . escapeshellarg($full_path_xml));
      
      $config_max_result = \Drupal::config('tripal_blast.settings')
        ->get('tripal_blast_config_jobs.max_result');
      
      $max_results = intval($config_max_result);
      if ($blast_job->num_results < $max_results) {
        $blast_job->xml = simplexml_load_file($full_path_xml);
      }
      else {
        $blast_job->too_many_results = TRUE;
      }
    }

    // Set ourselves up to do link-out if our blast database is configured to do so.
    $linkout = FALSE;
    if ($blast_job->blastdb->linkout->none === FALSE) {
      $linkout_type  = $blast_job->blastdb->linkout->type;
      $linkout_regex = $blast_job->blastdb->linkout->regex;

      // Note that URL prefix is not required if linkout type is 'custom'
      if (isset($blast_job->blastdb->linkout->db_id->urlprefix) && !empty($blast_job->blastdb->linkout->db_id->urlprefix)) {
        $linkout_urlprefix = $blast_job->blastdb->linkout->db_id->urlprefix;
      }

      // Check that we can determine the linkout URL.
      // (ie: that the function specified to do so, exists).
      if (function_exists($blast_job->blastdb->linkout->url_function)) {
        $url_function = $blast_job->blastdb->linkout->url_function;
        $linkout = TRUE;
      }
    }

    return $blast_job;
  }
}