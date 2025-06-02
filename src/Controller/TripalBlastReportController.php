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
    $report = NULL;

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
        $blast_job->blast_form_url = \Drupal\Core\Link::fromTextAndUrl($this->t($name), $link);

        break;
      }
    }

    // Load the XML file.
    // Add to markup.
    $blast_job->xml = TRUE; //NULL; @TODO change value.
    $blast_job->num_results = FALSE;
    $blast_job->too_many_results = FALSE;

    $full_path_xml = $blast_job->files->result->xml;
    if (is_readable($full_path_xml)) {
      $blast_job->num_results = shell_exec('grep -c "<Hit>" ' . escapeshellarg($full_path_xml));

      $max_results = \Drupal::config('tripal_blast.settings')
        ->get('tripal_blast_config_jobs.max_result');

      if ($max_results == 0 || $blast_job->num_results < $max_results) {
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

    // Convert file paths into relative paths for the report page to create links
    $blast_job->files->result->archive = str_replace(DRUPAL_ROOT . '/', '', $blast_job->files->result->archive);
    $blast_job->files->result->xml = str_replace(DRUPAL_ROOT . '/', '', $blast_job->files->result->xml);
    $blast_job->files->result->tsv = str_replace(DRUPAL_ROOT . '/', '', $blast_job->files->result->tsv);
    $blast_job->files->result->html = str_replace(DRUPAL_ROOT . '/', '', $blast_job->files->result->html);
    $blast_job->files->result->gff = str_replace(DRUPAL_ROOT . '/', '', $blast_job->files->result->gff);

    $blast_job->hola = is_bool($blast_job->xml) ? '' : $this->createXMLTableReport($blast_job->xml, $blast_job->linkout, $blast_job->no_hits);
    return $blast_job;
  }

  function createXMLTableReport($xml, $linkout, $no_hits) {
    // Specify the header of the table
    $header = array(
      'arrow-col' =>  array('data' => '', 'class' => array('arrow-col')),
      'number' =>  array('data' => '#', 'class' => array('number')),
      'query' =>  array('data' => 'Query Name  (Click for alignment & visualization)', 'class' => array('query')),
      'hit' =>  array('data' => 'Target Name', 'class' => array('hit')),
      'evalue' =>  array('data' => 'E-Value', 'class' => array('evalue')),
    );

    $rows = array();
    $count = 0;

    // Parse the BLAST XML to generate the rows of the table
    // where each hit results in two rows in the table: 1) A summary of the query/hit and
    // significance and 2) additional information including the alignment
    foreach ($xml->{'BlastOutput_iterations'}->children() as $iteration) {
      $children_count = $iteration->{'Iteration_hits'}->children()->count();

      // Save some information needed for the hit visualization.
      $target_name = '';
      $q_name = $xml->{'BlastOutput_query-def'};
      $query_size = $xml->{'BlastOutput_query-len'};
      $target_size = $iteration->{'Iteration_stat'}->{'Statistics'}->{'Statistics_db-len'};

      if ($children_count != 0) {
        foreach ($iteration->{'Iteration_hits'}->children() as $hit) {
          if (is_object($hit)) {
            $count += 1;
            $zebra_class = ($count % 2 == 0) ? 'even' : 'odd';
            $no_hits = FALSE;

            // SUMMARY ROW
            // -- Save additional information needed for the summary.
            $score = (float) $hit->{'Hit_hsps'}->{'Hsp'}->{'Hsp_score'};
            $evalue = (float) $hit->{'Hit_hsps'}->{'Hsp'}->{'Hsp_evalue'};
            $query_name = (string) $iteration->{'Iteration_query-def'};

            // If the id is of the form gnl|BL_ORD_ID|### then the parseids flag
            // to makeblastdb did a really poor job. In thhis case we want to use
            // the def to provide the original FASTA header.
            // @todo Deepak changed this to use just the hit_def; inquire as to why.
            $hit_name = (preg_match('/BL_ORD_ID/', $hit->{'Hit_id'})) ? $hit->{'Hit_def'} : $hit->{'Hit_id'};
            // Used for the hit visualization to ensure the name isn't truncated.
            $hit_name_short = (preg_match('/^([^\s]+)/', $hit_name, $matches)) ? $matches[1] : $hit_name;

            // Round e-value to two decimal values.
            $rounded_evalue = '';
            if (strpos($evalue, 'e') != false) {
              $evalue_split = explode('e', $evalue);
              $rounded_evalue = round($evalue_split[0], 2, PHP_ROUND_HALF_EVEN);
              $rounded_evalue .= 'e' . $evalue_split[1];
            } else {
              $rounded_evalue = $evalue;
            }

            // State what should be in the summary row for theme_table() later.
            $summary_row = array(
              'data' => array(
                'arrow-col' => array('data' => \Drupal\Core\Render\Markup::create('<div class="arrow"></div>'), 'class' => array('arrow-col')),
                'number' => array('data' => $count, 'class' => array('number')),
                'query' => array('data' => $query_name, 'class' => array('query')),
                'hit' => array('data' => $hit_name, 'class' => array('hit')),
                'evalue' => array('data' => $rounded_evalue, 'class' => array('evalue')),
              ),
              'class' => array('result-summary')
            );

            // ALIGNMENT ROW (collapsed by default)
            // Process HSPs
            $HSPs = array();

            // We need to save some additional summary information in order to draw the
            // hit visualization. First, initialize some variables...
            $track_start = INF;
            $track_end = -1;
            $hsps_range = '';
            $hit_hsps = '';
            $hit_hsp_score = '';
            $target_size = $hit->{'Hit_len'};
            $Hsp_bit_score = '';

            // Then for each hit hsp, keep track of the start of first hsp and the end of
            // the last hsp. Keep in mind that hsps might not be recorded in order.
            foreach ($hit->{'Hit_hsps'}->children() as $hsp_xml) {
              $HSPs[] = (array) $hsp_xml;

              if ($track_start > $hsp_xml->{'Hsp_hit-from'}) {
                $track_start = $hsp_xml->{'Hsp_hit-from'} . "";
              }
              if ($track_end < $hsp_xml->{'Hsp_hit-to'}) {
                $track_end = $hsp_xml->{'Hsp_hit-to'} . "";
              }

              // The BLAST visualization code requires the hsps to be formatted in a
              // very specific manner. Here we build up the strings to be submitted.
              // hits=4263001_4262263_1_742;4260037_4259524_895_1411;&scores=722;473;
              $hit_hsps .=  $hsp_xml->{'Hsp_hit-from'} . '_' .
                $hsp_xml->{'Hsp_hit-to'} . '_' .
                $hsp_xml->{'Hsp_query-from'} . '_' . $hsp_xml->{'Hsp_query-to'} .
                ';';
              $Hsp_bit_score .=   $hsp_xml->{'Hsp_bit-score'} . ';';
            }
            // Finally record the range.
            // @todo figure out why we arbitrarily subtract 50,000 here...
            // @more removing the 50,000 and using track start/end appears to cause no change...
            $range_start = (int) $track_start; // - 50000;
            $range_end = (int) $track_end; // + 50000;
            if ($range_start < 1) $range_start = 1;


            // Call the function to generate the hit image.
            $hit_img = $this->generateBlastHitImage(
              $target_name,
              $Hsp_bit_score,
              $hit_hsps,
              $target_size,
              $query_size,
              $q_name,
              $hit_name_short
            );


            // State what should be in the alignment row for theme_table() later.
            $alignment_row = array(
              'data' => array(
                'arrow' => array(
                  'data' => [
                    '#theme' => 'blast_report_alignment_row',
                    '#HSPs' => $HSPs,
                    '#hit_visualization' => $hit_img
                  ],
                  'colspan' => 5,
                ),
              ),
              'class' => array('alignment-row', $zebra_class),
              'no_striping' => TRUE
            );

            // LINK-OUTS.
            // It was determined above whether link-outs were supported for the
            // tripal blast database used as a search target. Thus we only want to
            // determine a link-out if it's actually supported... ;-)
            if ($linkout) {

              // First extract the linkout text using the regex provided through
              // the Tripal blast database node.
              if (preg_match($linkout_regex, $hit_name, $linkout_match)) {
                $hit->{'linkout_id'} = $linkout_match[1];
                $hit->{'hit_name'} = $hit_name;

                // Allow custom functions to determine the URL to support more complicated
                // link-outs rather than just using the tripal database prefix.
                $hit_name = call_user_func(
                  $url_function,
                  $linkout_urlprefix,
                  $hit,
                  array(
                    'query_name' => $query_name,
                    'score'      => $score,
                    'e-value'    => $evalue,
                    'HSPs'       => $HSPs,
                    'Target'     => $blast_job->blastdb->db_name,
                  )
                );
              }

              // Replace the target name with the link.
              $summary_row['data']['hit']['data'] = $hit_name;
            }

            // ADD TO TABLE ROWS
            $rows[] = $summary_row;
            $rows[] = $alignment_row;
          } //end of if - checks $hit
        } //end of foreach - iteration_hits
      } //end of if - check for iteration_hits

      else {
        // Currently where the "no results" is added.
        $query_name = $iteration->{'Iteration_query-def'};
        $query_with_no_hits[] = $query_name;
      } //no results
    } //end of foreach - BlastOutput_iterations

    if ($no_hits) {
      return '<p class="no-hits-message">No results found.</p>';
    } else {
      // We want to warn the user if some of their query sequences had no hits.
      if (!empty($query_with_no_hits)) {
        return '<p class="no-hits-message">Some of your query sequences did not '
          . 'match to the database/template. They are: '
          . implode(', ', $query_with_no_hits) . '.</p>';
      }

      // Actually print the table.
      if (!empty($rows)) {
        return [
          '#type' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#attributes' => ['id' => 'blast_report'],
          '#sticky' => FALSE
        ];
      }
    } //handle no hits
  }

  /**
   * Generate an image of HSPs for a given hit.
   *
   * history:
   *    09/23/10  Carson  created
   *    04/16/12  eksc    adapted into POPcorn code
   *    03/12/15  deepak  Adapted code into Tripal BLAST
   *    10/23/15  lacey   Fixed deepak's code to be suitable for Drupal.
   *
   * @param $acc
   *    target name
   * @param $name
   *    query name, false if none
   * @param $tsize
   *    target size
   * @param $qsize
   *    query size
   * @param $hits
   *    each hit represented in URL as: targetstart_targetend_hspstart_hspend;
   * @param $score
   *    score for each hit
   *
   * @returm
   *    A base64 encoded image representing the hit information.
   */
  function generateBlastHitImage($acc, $scores, $hits, $tsize, $qsize, $name, $hit_name) {
    $tok = strtok($hits, ";");
    $b_hits = array();
    while ($tok !== false) {
      $b_hits[] = $tok;
      $tok = strtok(";");
    }

    // extract score information from score param
    $tokscr = strtok($scores, ";");
    $b_scores = array();
    while ($tokscr !== false) {
      $b_scores[] = $tokscr;
      $tokscr = strtok(";");
    }

    // image measurements
    $height = 200 + (count($b_hits) * 16);
    $width  = 520;

    $img = imagecreatetruecolor($width, $height);

    $white      = imagecolorallocate($img, 255, 255, 255);
    $black      = imagecolorallocate($img, 0, 0, 0);
    $darkgray   = imagecolorallocate($img, 100, 100, 100);
    $strong     = imagecolorallocatealpha($img, 202, 0, 0, 15);
    $moderate   = imagecolorallocatealpha($img, 204, 102, 0, 20);
    $present    = imagecolorallocatealpha($img, 204, 204, 0, 35);
    $weak       = imagecolorallocatealpha($img, 102, 204, 0, 50);
    $gray       = imagecolorallocate($img, 190, 190, 190);
    $lightgray  = $white; //imagecolorallocate($img, 230, 230, 230);

    imagefill($img, 0, 0, $lightgray);

    // Target coordinates
    $maxlength = 300;
    $t_length = ($tsize > $qsize)
      ? $maxlength : $maxlength - 50;
    $q_length = ($qsize > $tsize)
      ? $maxlength : $maxlength - 50;

    $tnormal = $t_length / $tsize;
    $qnormal = $q_length / $qsize;

    $t_ystart = 30;
    $t_yend   = $t_ystart + 20;

    $t_xstart = 50;
    $t_xend   = $t_xstart + $t_length;
    $t_center = $t_xstart + ($t_length / 2);

    // Target labels
    $warn = '"' . $hit_name . '"';
    imagestring($img, 5, $t_xstart, $t_ystart - 20, $acc . $warn, $black);
    imagestring($img, 3, 5, $t_ystart + 2, "Target", $black);

    // Draw bar representing target
    imagefilledrectangle($img, $t_xstart, $t_ystart, $t_xend, $t_yend, $gray);
    imagerectangle($img, $t_xstart, $t_ystart, $t_xend, $t_yend, $darkgray);

    // query coordinates
    $q_maxheight = 250;
    $q_ystart = $t_yend + 100;
    $q_yend = $q_ystart + 20;

    $q_xstart = $t_center - $q_length / 2;
    $q_xend = $q_xstart + $q_length;

    $q_center = ($q_xend + $q_xstart) / 2;
    $q_xwidth = $q_xend - $q_xstart;

    // Query labels
    imagestring($img, 5, $q_xstart, $q_yend + 2, $name, $black);
    imagestring($img, 3, $q_xstart, $q_ystart + 2, 'Query', $black);

    // Draw bar representing query
    imagefilledrectangle($img, $q_xstart, $q_ystart, $q_xend, $q_yend, $gray);
    imagerectangle($img, $q_xstart, $q_ystart, $q_xend, $q_yend, $darkgray);

    // HSP bars will start here
    $hsp_bary = $q_yend + 20;

    // Draw solids for HSP alignments
    for ($ii = count($b_hits) - 1; $ii >= 0; $ii--) {
      // alignment

      $cur_hit = $b_hits[$ii];
      $cur_score = intval($b_scores[$ii]);

      // set color according to score
      $cur_color = $darkgray;
      if ($cur_score > 200) {
        $cur_color = $strong;
      } else if ($cur_score > 80 && $cur_score <= 200) {
        $cur_color = $moderate;
      } else if ($cur_score > 50 && $cur_score <= 80) {
        $cur_color = $present;
      } else if ($cur_score > 40 && $cur_score <= 50) {
        $cur_color = $weak;
      }

      $t_start = $tnormal *  intval(strtok($cur_hit, "_")) + $t_xstart;
      $t_end = $tnormal *  intval(strtok("_")) + $t_xstart;
      $q_start = $qnormal * intval(strtok("_")) + $q_xstart;
      $q_end = $qnormal *  intval(strtok("_")) + $q_xstart;

      $hit1_array = array(
        $t_start,
        $t_yend,
        $t_end,
        $t_yend,
        $q_end,
        $q_ystart,
        $q_start,
        $q_ystart
      );

      // HSP coords
      imagefilledpolygon($img, $hit1_array, $cur_color);
    } //each hit

    // Draw lines over fills for HSP alignments
    for ($ii = 0; $ii < count($b_hits); $ii++) {
      // alignment

      $cur_hit = $b_hits[$ii];
      $t_start = (int) round($tnormal *  intval(strtok($cur_hit, "_")) + $t_xstart);
      $t_end = (int) round($tnormal *  intval(strtok("_")) + $t_xstart);
      $q_start = (int) round($qnormal * intval(strtok("_")) + $q_xstart);
      $q_end = (int) round($qnormal *  intval(strtok("_")) + $q_xstart);

      $hit1_array = array(
        $t_start,
        $t_yend,
        $t_end,
        $t_yend,
        $q_end,
        $q_ystart,
        $q_start,
        $q_ystart,
      );

      imagerectangle($img, $t_start, $t_ystart, $t_end, $t_yend, $black);
      imagerectangle($img, $q_start, $q_ystart, $q_end, $q_yend, $black);
      imagepolygon($img, $hit1_array, $black);

      // show HSP

      imagestring($img, 3, 2, $hsp_bary, ($acc . "HSP" . ($ii + 1)), $black);

      $cur_score = intval($b_scores[$ii]);

      // set color according to score
      $cur_color = $darkgray;
      if ($cur_score > 200) {
        $cur_color = $strong;
      } else if ($cur_score > 80 && $cur_score <= 200) {
        $cur_color = $moderate;
      } else if ($cur_score > 50 && $cur_score <= 80) {
        $cur_color = $present;
      } else if ($cur_score > 40 && $cur_score <= 50) {
        $cur_color = $weak;
      }

      imagefilledrectangle($img, $q_start, $hsp_bary, $q_end, $hsp_bary + 10, $cur_color);
      $hsp_bary += 15;
    } //each hit

    // Draw the key

    $xchart = 390;
    $ychart = 10;
    $fontsize = 4;
    $yinc = 20;
    $ywidth = 7;
    $xinc = 10;

    imagestring($img, 5, $xchart, $ychart - 5, "Bit Scores", $black);

    imagestring($img, $fontsize, $xchart + $yinc + $xinc, $ychart + ($yinc * 1) + $ywidth, ">= 200", $black);
    imagestring($img, $fontsize, $xchart + $yinc + $xinc, $ychart + ($yinc * 2) + $ywidth, "80 - 200", $black);
    imagestring($img, $fontsize, $xchart + $yinc + $xinc, $ychart + ($yinc * 3) + $ywidth, "50 - 80", $black);
    imagestring($img, $fontsize, $xchart + $yinc + $xinc, $ychart + ($yinc * 4) + $ywidth, "40 - 50", $black);
    imagestring($img, $fontsize, $xchart + $yinc + $xinc, $ychart + ($yinc * 5) + $ywidth, "< 40", $black);

    imagefilledRectangle($img, $xchart, $ychart + ($yinc * 1) + $xinc, $xchart + $yinc, $ychart + ($yinc * 2), $strong);
    imagefilledRectangle($img, $xchart, $ychart + ($yinc * 2) + $xinc, $xchart + $yinc, $ychart + ($yinc * 3), $moderate);
    imagefilledRectangle($img, $xchart, $ychart + ($yinc * 3) + $xinc, $xchart + $yinc, $ychart + ($yinc * 4), $present);
    imagefilledRectangle($img, $xchart, $ychart + ($yinc * 4) + $xinc, $xchart + $yinc, $ychart + ($yinc * 5), $weak);
    imagefilledRectangle($img, $xchart, $ychart + ($yinc * 5) + $xinc, $xchart + $yinc, $ychart + ($yinc * 6), $darkgray);

    // Now, we have a completed image resource and need to change it to an actual image
    // that can be displayed. This is done using imagepng() but unfortuatly that function
    // either saves the image to a file or outputs it directly to the screen. Thus, we use
    // the following code to capture it and base64 encode it.
    ob_start(); // Start buffering the output
    imagepng($img, null, 0, PNG_NO_FILTER);
    $b64_img = base64_encode(ob_get_contents()); // Get what we've just outputted and base64 it
    imagedestroy($img);
    ob_end_clean();

    return $b64_img;
  }
}
