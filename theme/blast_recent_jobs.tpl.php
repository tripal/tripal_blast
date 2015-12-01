<?php
/**
 *
 */

// Gets the list of recent jobs filtered to the current blast program (ie: blastn).
$recent_jobs = get_recent_blast_jobs($programs);
if ($recent_jobs) {

  usort($recent_jobs, 'sort_blast_jobs_by_date_submitted_desc');
  
  print '<h2>Recent Jobs</h2>';
  
  $table = array(
    'header' => array('Query Information', 'Search Target', 'Date Requested', ''),
    'rows' => array(),
    'attributes' => array('class' => array('tripal-blast', 'recent-jobs')),
    'sticky' => FALSE
  );

  foreach ($recent_jobs as $job) {

    // Define a row for the current job.
    $table['rows'][] = array(
      $job->query_summary,
      $job->blastdb->db_name,
      format_date($job->date_submitted, 'medium'),
      l('See Results', 'blast/report/'.blast_ui_make_secret($job->job_id))
    );
  }
  
  print theme('table', $table);
}
?>