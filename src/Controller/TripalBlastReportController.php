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

  /**
   * How long to wait in seconds for the job runner to start a job.
   *
   * @var int
   */
  protected int $max_start_delay = 120;

  /**
   * Checks on job status and generates appropriate twig values.
   *
   * @param string $job_id
   *   Alphanumeric unique job identifier.
   */
  public function report(string $job_id) {
    $report = NULL;
    // BLASTs are run as a Tripal job. As such we need to determine whether
    // the current BLAST is in the queue, running, or complete in order to
    // determine what to show the user.
    // Decode the alphanumeric job_id.
    $job_service = \Drupal::service('tripal_blast.job_service');
    $blastjob_id = $job_service->jobsBlastRevealSecret($job_id);

    $tripaljob = new TripalJob();
    $tripaljob->load($blastjob_id);
    $job = $tripaljob->getJob();

    if ($job->start_time === NULL && $job->end_time === NULL) {
      $start_delay = time() - $job->submit_date;
      if ($start_delay > $this->max_start_delay) {
        // Cancel the job so stalled jobs don't pile up.
        $tripaljob->cancel();
        $theme = 'theme-tripal-blast-report-pending';
        $job_param = [
          'job_id' => '',
          'status' => 'Error',
          'status_code' => 2,
        ];
      }
      else {
        // 1) Job is in the Queue.
        $theme = 'theme-tripal-blast-report-pending';
        $job_param = [
          'job_id' => '',
          'status' => 'Pending',
          'status_code' => 0,
          'refresh_time' => 5000,
        ];
      }
    }
    elseif (strtolower($job->status) == 'cancelled') {
      // 2) Job has been Cancelled.
      $theme = 'theme-tripal-blast-report-pending';
      $job_param = [
        'job_id' => '',
        'status' => 'Cancelled',
        'status_code' => 999,
      ];
    }
    elseif (strtolower($job->status) == 'error') {
      // 2) Job has encountered an error.
      $theme = 'theme-tripal-blast-report-pending';
      $job_param = [
        'job_id' => '',
        'status' => 'Error',
        'status_code' => 666,
      ];
    }
    elseif ($job->end_time !== NULL) {
      // 3) Job is Complete
      $theme = 'theme-tripal-blast-show-report';
      $job_param = [
        'job_id' => $blastjob_id,
        'status' => '',
        'status_code' => '',
      ];

      $report = $this->prepareReport($blastjob_id);
    }
    else {
      // 4) Job is in Progress
      $run_time = time() - $job->submit_date;
      // Automatically refresh the page less frequently for long run times.
      // Add approximately 5000 milliseconds for each minute of run time.
      $refresh_time_ms = (int) ($run_time * 83.3 + 5000);
      $theme = 'theme-tripal-blast-report-pending';
      $job_param = [
        'job_id' => '',
        'status' => 'Running',
        'status_code' => 1,
        'refresh_time' => $refresh_time_ms,
        'run_time' => gmdate($run_time),
      ];
    }

    return [
      '#theme' => $theme,
      '#attached' => [
        'library' => ['tripal_blast/tripal-blast-report'],
      ],
      '#report' => $report,
      '#job' => $job_param,
    ];
  }

  /**
   * Prepare report page.
   *
   * @param int $blastjob_id
   *   Value of job_id in the public.blastjob table.
   *
   * @return string
   *   Report page markup.
   */
  public function prepareReport(int $blastjob_id) {
    $logger = \Drupal::logger('tripal_blast');

    // Get job profile.
    $job_service = \Drupal::service('tripal_blast.job_service');
    /* @var stdClass */
    $blast_job = $job_service->jobsGetJobByJobId($blastjob_id, ['skip_file_check' => TRUE]);

    // Get report settings.
    $blast_job->wrap_length = \Drupal::config('tripal_blast.settings')
      ->get('tripal_blast_config_report.wrap_length');

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
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Unable to generate the BLAST command for execution. Please contact the site administrator.'));
      $logger->error('Unable to generate the BLAST command for execution for job ID @blastjob_id. The error was: @error',
        ['@blastjob_id' => $blastjob_id, '@error' => $e->getMessage()]);
    }

    // Determine the URL of the blast form.
    $blast_programs = [
      'blastn' => ['nucleotide', 'nucleotide'],
      'blastx' => ['nucleotide', 'protein'],
      'tblastn' => ['protein', 'nucleotide'],
      'blastp' => ['protein', 'protein'],
    ];
    $route_ui = 'tripal_blast.blast_program';

    foreach ($blast_programs as $name => $param) {
      if ($name == $blast_job->program) {
        [$query, $db] = $param;
        $link = Url::fromRoute($route_ui, ['query' => $query, 'db' => $db]);
        // Add to markup.
        $blast_job->blast_form_url = Link::fromTextAndUrl($name, $link)->toString();

        break;
      }
    }

    // Load the XML file.
    // Add to markup.
    $blast_job->xml = NULL;
    $blast_job->num_results = FALSE;
    $blast_job->too_many_results = FALSE;
    $blast_job->table_markup = 'undef';

    $full_path_xml = $blast_job->files->result['xml']['absolute_path'];
    if (is_readable($full_path_xml)) {
      // This gets the hit count without loading the xml, which could
      // cause memory issues if a massive number of hits exist.
      $blast_job->num_results = shell_exec('grep -c "<Hit>" ' . escapeshellarg($full_path_xml));

      $max_results = \Drupal::config('tripal_blast.settings')
        ->get('tripal_blast_config_jobs.max_result');

      if ($blast_job->num_results <= $max_results) {
        $blast_job->xml = simplexml_load_file($full_path_xml);
        // Blast hit row information for the blast results table and images.
        $blast_job->tablerows = $this->generateBlastTableRows($blast_job);
      }
      else {
        $blast_job->too_many_results = TRUE;
      }
    }

    $blast_job->num_results_formatted = number_format(floatval($blast_job->num_results));

    $blast_job->linkout = FALSE;
    if ($blast_job->blastdb->linkout->none === FALSE) {
      $blast_job->linkout_type = $blast_job->blastdb->linkout->type;
      $blast_job->linkout_regex = $blast_job->blastdb->linkout->regex;

      // Note that URL prefix is not required if linkout type is 'custom'.
      if (isset($blast_job->blastdb->linkout->db_id->urlprefix) && !empty($blast_job->blastdb->linkout->db_id->urlprefix)) {
        $blast_job->linkout_urlprefix = $blast_job->blastdb->linkout->db_id->urlprefix;
      }

      // Check that we can determine the linkout URL.
      // (i.e.: that the function specified to do so, exists).
      if (function_exists($blast_job->blastdb->linkout->url_function)) {
        $blast_job->url_function = $blast_job->blastdb->linkout->url_function;
        $blast_job->linkout = TRUE;
      }
    }

    $blast_job->submission_date = \Drupal::service('date.formatter')
      ->format($blast_job->date_submitted, 'medium');

    // Handle no hits. This following array will hold the names of all query
    // sequences which didn't have any hits.
    $blast_job->query_with_no_hits = [];

    // Furthermore, if no query sequences have hits then we don't want to
    // bother listing them all, but just want to give a single, all-inclusive
    // "No Results" message.
    $blast_job->no_hits = TRUE;

    return $blast_job;
  }

  /**
   * Prepares graphical blast results html markup.
   *
   * @param \stdClass $blast_job
   *   BLAST results. The primary property used here is xml.
   *
   * @return array
   *   An array of blast hit rows to populate the twig table template.
   *   This will be passed as report.tablerows.
   *   For each row, columns are:
   *   - arrow-col:
   *   - number:
   *   - query:
   *   - hit:
   *   - evalue:
   *   - HSPs:
   *   - hit_visualization:
   */
  protected function generateBlastTableRows(\stdClass $blast_job): array {
    $tablerows = [];
    if ($blast_job->xml) {

      $count = 0;

      // Parse the BLAST XML to generate the rows of the table
      // where each hit results in two rows in the table:
      // 1) A summary of the query/hit and significance, and
      // 2) additional information including the alignment.
      foreach ($blast_job->xml->{'BlastOutput_iterations'}->children() as $iteration) {
        $children_count = $iteration->{'Iteration_hits'}->children()->count();

        // Save some information needed for the hit visualization.
        $target_name = '';
        $q_name = $blast_job->xml->{'BlastOutput_query-def'};
        $query_size = $blast_job->xml->{'BlastOutput_query-len'};
        $target_size = $iteration->{'Iteration_stat'}->{'Statistics'}->{'Statistics_db-len'};

        if ($children_count != 0) {
          foreach ($iteration->{'Iteration_hits'}->children() as $hit) {
            if (is_object($hit)) {
              $count += 1;

              // SUMMARY ROW.
              // Save additional information needed for the summary.
              $score = (float) $hit->{'Hit_hsps'}->{'Hsp'}->{'Hsp_score'};
              $evalue = (float) $hit->{'Hit_hsps'}->{'Hsp'}->{'Hsp_evalue'};
              $query_name = (string) $iteration->{'Iteration_query-def'};

              // If the id is of the form gnl|BL_ORD_ID|### then the parseids
              // flag to makeblastdb did a really poor job. In this case we
              // want to use the def to provide the original FASTA header.
              // @todo Deepak changed this to use just the hit_def;
              // inquire as to why.
              $hit_name = (preg_match('/BL_ORD_ID/', $hit->{'Hit_id'})) ? $hit->{'Hit_def'} : $hit->{'Hit_id'};
              // Used for the hit visualization to ensure that the name
              // isn't truncated.
              $hit_name_short = (preg_match('/^([^\s]+)/', $hit_name, $matches)) ? $matches[1] : $hit_name;

              // Round e-value to two decimal values.
              $rounded_evalue = '';
              if (strpos($evalue, 'e') != FALSE) {
                $evalue_split = explode('e', $evalue);
                $rounded_evalue = round($evalue_split[0], 2, PHP_ROUND_HALF_EVEN);
                $rounded_evalue .= 'e' . $evalue_split[1];
              }
              else {
                $rounded_evalue = $evalue;
              }

              // ALIGNMENT ROW (collapsed by default).
              // Process HSPs.
              $hsps = [];

              // We need to save some additional summary information in order
              // to draw the hit visualization. Initialize those variables.
              $track_start = INF;
              $track_end = -1;
              $hit_hsps = '';
              $target_size = $hit->{'Hit_len'};
              $hsp_bit_score = '';

              // Then for each hit hsp, keep track of the start of the first
              // hsp and the end of the last hsp. Keep in mind that hsps
              // might not be recorded in order.
              foreach ($hit->{'Hit_hsps'}->children() as $hsp_xml) {
                $hsp_array = (array) $hsp_xml;

                if ($track_start > $hsp_xml->{'Hsp_hit-from'}) {
                  $track_start = $hsp_xml->{'Hsp_hit-from'};
                }
                if ($track_end < $hsp_xml->{'Hsp_hit-to'}) {
                  $track_end = $hsp_xml->{'Hsp_hit-to'};
                }

                // Add some calculated and formatted values.
                $hsp_array['Hsp_align-pct'] = round($hsp_xml->{'Hsp_identity'} * 100 / $hsp_xml->{'Hsp_align-len'}, 2, PHP_ROUND_HALF_EVEN);
                $hsp_array['Hsp_positive-pct'] = round($hsp_xml->{'Hsp_positive'} * 100 / $hsp_xml->{'Hsp_align-len'}, 2, PHP_ROUND_HALF_EVEN);

                // The BLAST visualization code requires the hsps to be
                // formatted in a very specific manner. Here we build
                // up the strings to be submitted. For example:
                // hits=4263001_4262263_1_742;4260037_4259524_895_1411;&scores=722;473;.
                $hit_hsps .= implode('_', [
                  $hsp_array['Hsp_hit-from'],
                  $hsp_array['Hsp_hit-to'],
                  $hsp_array['Hsp_query-from'],
                  $hsp_array['Hsp_query-to'],
                ]) . ';';
                $hsp_bit_score .= $hsp_xml->{'Hsp_bit-score'} . ';';

                // Wrap the alignment, and remove the unwrapped version from
                // HSP so that we don't pass it to the twig template.
                $hsp_array['Hsp_alignment'] = $this->wrapAlignment($hsp_array, $blast_job->wrap_length);
                unset($hsp_array['Hsp_qseq']);
                unset($hsp_array['Hsp_midline']);
                unset($hsp_array['Hsp_hseq']);

                $hsps[] = $hsp_array;
              }

              // Finally record the range.
              $range_start = (int) $track_start;
              if ($range_start < 1) {
                $range_start = 1;
              }

              // Call the function to generate the hit image.
              $hit_img = $this->generateBlastHitImage($target_name, $hsp_bit_score, $hit_hsps, $target_size, $query_size, $q_name, $hit_name_short);

              // Generate link-outs.
              // It was determined above whether link-outs were supported
              // for the tripal blast database used as a search target.
              // We can only generate a link-out if it's actually supported
              // for this database.
              if ($blast_job->linkout) {

                // First extract the linkout text using the regex provided
                // through the Tripal blast database node.
                if (preg_match($blast_job->linkout_regex, $hit_name, $blast_job->linkout_match)) {
                  $hit->{'linkout_id'} = $linkout_match[1];
                  $hit->{'hit_name'} = $hit_name;

                  // Allow custom functions to determine the URL to support
                  // more complicated link-outs rather than just using the
                  // tripal database prefix.
                  $hit_name = call_user_func(
                    $blast_job->url_function,
                    $blast_job->linkout_urlprefix,
                    $hit,
                    [
                      'query_name' => $query_name,
                      'score' => $score,
                      'e-value' => $evalue,
                      'HSPs' => $hsps,
                      'Target' => $blast_job->blastdb->db_name,
                    ]
                  );
                }
              }

              // Add a record to tablerows. This is what we eventually
              // pass to the twig template.
              $tablerows[] = [
                'number' => $count,
                'query' => $query_name,
                'hit' => $hit_name,
                'evalue' => $rounded_evalue,
                'HSPs' => $hsps,
                'hit_visualization' => $hit_img,
              ];
            }
          }
        }
      }
    }
    return $tablerows;
  }

  /**
   * Generate an image of HSPs for a given hit.
   *
   * History:
   *    09/23/10  Carson  created.
   *    04/16/12  eksc    adapted into POPcorn code.
   *    03/12/15  deepak  Adapted code into Tripal BLAST.
   *    10/23/15  lacey   Fixed deepak's code to be suitable for Drupal.
   *    09/20/26  senalik Port to tripal 4.
   *
   * @param string $acc
   *   Target name.
   * @param string $scores
   *   Score for each hit.
   * @param string $hits
   *   Each hit represented in URL as: targetstart_targetend_hspstart_hspend.
   * @param SimpleXMLElement $tsize
   *   Target size.
   * @param SimpleXMLElement $qsize
   *   Query size.
   * @param string|bool $name
   *   Query name, FALSE if none.
   * @param string $hit_name
   *   Hit name.
   *
   * @return string
   *   A base64 encoded image representing the hit information.
   */
  protected function generateBlastHitImage(
    string $acc,
    string $scores,
    string $hits,
    \SimpleXMLElement $tsize,
    \SimpleXMLElement $qsize,
    string|bool $name,
    string $hit_name,
  ): string {

    if (!$acc) {
      $acc = '';
    }
    $tok = strtok($hits, ';');
    $b_hits = [];
    while ($tok !== FALSE) {
      $b_hits[] = $tok;
      $tok = strtok(';');
    }

    // Extract score information from score param.
    $tokscr = strtok($scores, ';');
    $b_scores = [];
    while ($tokscr !== FALSE) {
      $b_scores[] = $tokscr;
      $tokscr = strtok(';');
    }

    // Image measurements.
    $height = 200 + (count($b_hits) * 16);
    $width = 520;

    $img = imagecreatetruecolor($width, $height);

    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    $darkgray = imagecolorallocate($img, 100, 100, 100);
    $strong = imagecolorallocatealpha($img, 202, 0, 0, 15);
    $moderate = imagecolorallocatealpha($img, 204, 102, 0, 20);
    $present = imagecolorallocatealpha($img, 204, 204, 0, 35);
    $weak = imagecolorallocatealpha($img, 102, 204, 0, 50);
    $gray = imagecolorallocate($img, 190, 190, 190);
    $background = $white;

    imagefill($img, 0, 0, $background);

    // Target coordinates.
    $maxlength = 300;
    $t_length = ($tsize > $qsize) ? $maxlength : $maxlength - 50;
    $q_length = ($qsize > $tsize) ? $maxlength : $maxlength - 50;

    $tnormal = $t_length / $tsize;
    $qnormal = $q_length / $qsize;

    $t_ystart = 30;
    $t_yend = $t_ystart + 20;

    $t_xstart = 50;
    $t_xend = $t_xstart + $t_length;
    $t_center = $t_xstart + ($t_length / 2);

    // Target labels.
    $warn = '"' . $hit_name . '"';
    imagestring($img, 5, $t_xstart, $t_ystart - 20, $acc . $warn, $black);
    imagestring($img, 3, 5, $t_ystart + 2, 'Target', $black);

    // Draw bar representing target.
    imagefilledrectangle($img, $t_xstart, $t_ystart, $t_xend, $t_yend, $gray);
    imagerectangle($img, $t_xstart, $t_ystart, $t_xend, $t_yend, $darkgray);

    // Query coordinates.
    $q_ystart = $t_yend + 100;
    $q_yend = $q_ystart + 20;
    $q_xstart = $t_center - $q_length / 2;
    $q_xend = $q_xstart + $q_length;

    // Query labels.
    imagestring($img, 5, $q_xstart, $q_yend + 2, $name, $black);
    imagestring($img, 3, $q_xstart, $q_ystart + 2, 'Query', $black);

    // Draw bar representing query.
    imagefilledrectangle($img, $q_xstart, $q_ystart, $q_xend, $q_yend, $gray);
    imagerectangle($img, $q_xstart, $q_ystart, $q_xend, $q_yend, $darkgray);

    // HSP bars will start here.
    $hsp_bary = $q_yend + 20;

    // Draw solids for HSP alignments.
    for ($ii = count($b_hits) - 1; $ii >= 0; $ii--) {
      // Alignment.
      $cur_hit = $b_hits[$ii];
      $cur_score = intval($b_scores[$ii]);

      // Set color according to the score.
      $cur_color = $darkgray;
      if ($cur_score > 200) {
        $cur_color = $strong;
      }
      elseif ($cur_score > 80 && $cur_score <= 200) {
        $cur_color = $moderate;
      }
      elseif ($cur_score > 50 && $cur_score <= 80) {
        $cur_color = $present;
      }
      elseif ($cur_score > 40 && $cur_score <= 50) {
        $cur_color = $weak;
      }

      $t_start = intval($tnormal * intval(strtok($cur_hit, '_')) + $t_xstart);
      $t_end = intval($tnormal * intval(strtok('_')) + $t_xstart);
      $q_start = intval($qnormal * intval(strtok('_')) + $q_xstart);
      $q_end = intval($qnormal * intval(strtok('_')) + $q_xstart);

      $hit1_array = [$t_start, $t_yend, $t_end, $t_yend, $q_end, $q_ystart, $q_start, $q_ystart];

      // HSP coordinates.
      imagefilledpolygon($img, $hit1_array, $cur_color);

    }

    // Draw lines over fills for HSP alignments.
    for ($ii = 0; $ii < count($b_hits); $ii++) {
      // Alignment.
      $cur_hit = $b_hits[$ii];
      $t_start = intval($tnormal * intval(strtok($cur_hit, '_')) + $t_xstart);
      $t_end = intval($tnormal * intval(strtok('_')) + $t_xstart);
      $q_start = intval($qnormal * intval(strtok('_')) + $q_xstart);
      $q_end = intval($qnormal * intval(strtok('_')) + $q_xstart);

      $hit1_array = [$t_start, $t_yend, $t_end, $t_yend, $q_end, $q_ystart, $q_start, $q_ystart];

      imagerectangle($img, $t_start, $t_ystart, $t_end, $t_yend, $black);
      imagerectangle($img, $q_start, $q_ystart, $q_end, $q_yend, $black);
      imagepolygon($img, $hit1_array, $black);

      // Show HSP.
      imagestring($img, 3, 2, $hsp_bary, ($acc . 'HSP' . ($ii + 1)), $black);

      // Set color according to the score.
      $cur_score = intval($b_scores[$ii]);
      $cur_color = $darkgray;
      if ($cur_score > 200) {
        $cur_color = $strong;
      }
      elseif ($cur_score > 80 && $cur_score <= 200) {
        $cur_color = $moderate;
      }
      elseif ($cur_score > 50 && $cur_score <= 80) {
        $cur_color = $present;
      }
      elseif ($cur_score > 40 && $cur_score <= 50) {
        $cur_color = $weak;
      }

      imagefilledrectangle($img, $q_start, $hsp_bary, $q_end, $hsp_bary + 10, $cur_color);
      $hsp_bary += 15;
    }

    // Draw the key.
    $xchart = 390;
    $ychart = 10;
    $fontsize = 4;
    $yinc = 20;
    $ywidth = 7;
    $xinc = 10;

    imagestring($img, 5, $xchart, $ychart - 5, 'Bit Scores', $black);

    imagestring($img, $fontsize, $xchart + $yinc + $xinc, $ychart + ($yinc * 1) + $ywidth, '>= 200', $black);
    imagestring($img, $fontsize, $xchart + $yinc + $xinc, $ychart + ($yinc * 2) + $ywidth, '80 - 200', $black);
    imagestring($img, $fontsize, $xchart + $yinc + $xinc, $ychart + ($yinc * 3) + $ywidth, '50 - 80', $black);
    imagestring($img, $fontsize, $xchart + $yinc + $xinc, $ychart + ($yinc * 4) + $ywidth, '40 - 50', $black);
    imagestring($img, $fontsize, $xchart + $yinc + $xinc, $ychart + ($yinc * 5) + $ywidth, '< 40', $black);

    imagefilledrectangle($img, $xchart, $ychart + ($yinc * 1) + $xinc, $xchart + $yinc, $ychart + ($yinc * 2), $strong);
    imagefilledrectangle($img, $xchart, $ychart + ($yinc * 2) + $xinc, $xchart + $yinc, $ychart + ($yinc * 3), $moderate);
    imagefilledrectangle($img, $xchart, $ychart + ($yinc * 3) + $xinc, $xchart + $yinc, $ychart + ($yinc * 4), $present);
    imagefilledrectangle($img, $xchart, $ychart + ($yinc * 4) + $xinc, $xchart + $yinc, $ychart + ($yinc * 5), $weak);
    imagefilledrectangle($img, $xchart, $ychart + ($yinc * 5) + $xinc, $xchart + $yinc, $ychart + ($yinc * 6), $darkgray);

    // Now, we have a completed image resource and need to change it to an
    // actual image that can be displayed. This is done using imagepng(),
    // but unfortuatly that function either saves the image to a file or
    // outputs it directly to the screen. Thus, we use the following code
    // to capture it and base64 encode it.
    ob_start();
    imagepng($img, NULL, 0, PNG_NO_FILTER);
    // Get what we've just outputted and base64 encode it.
    $b64_img = base64_encode(ob_get_contents());
    ob_end_clean();

    return $b64_img;
  }

  /**
   * Wraps the sequence alignment at the specified length.
   *
   * @param array $hsp
   *   The HSP in array format.
   * @param int|null $wrap
   *   The line length to wrap to, defaults to 60.
   *
   * @return string
   *   HTML markup for the wrapped alignment.
   */
  protected function wrapAlignment(array $hsp, ?int $wrap = 60): string {
    $wrapped = '';

    $query = str_split($hsp['Hsp_qseq'], $wrap);
    $matches = str_split($hsp['Hsp_midline'], $wrap);
    $hit = str_split($hsp['Hsp_hseq'], $wrap);

    // Determine the max length of the coordinate string to use when padding.
    $coord_length = strlen($hsp['Hsp_hit-from']);
    $coord_length = (strlen($hsp['Hsp_query-to']) > $coord_length) ? strlen($hsp['Hsp_query-to']) : $coord_length;

    // Process each chunk determined above.
    $coord = [];
    foreach (array_keys($query) as $k) {
      // Determine the current coordinates.
      $coord['qstart'] = $hsp['Hsp_query-from'] + ($k * $wrap);
      if ($hsp['Hsp_hit-from'] < $hsp['Hsp_hit-to']) {
        $coord['hstart'] = $hsp['Hsp_hit-from'] + ($k * $wrap);
      }
      else {
        $coord['hstart'] = $hsp['Hsp_hit-from'] - ($k * $wrap);
      }
      $coord['qstop'] = $hsp['Hsp_query-from'] + (($k + 1) * $wrap) - 1;
      $coord['qstop'] = ($coord['qstop'] > $hsp['Hsp_query-to']) ? $hsp['Hsp_query-to'] : $coord['qstop'];
      if ($hsp['Hsp_hit-from'] < $hsp['Hsp_hit-to']) {
        $coord['hstop'] = $hsp['Hsp_hit-from'] + (($k + 1) * $wrap) - 1;
        $coord['hstop'] = ($coord['hstop'] > $hsp['Hsp_hit-to']) ? $hsp['Hsp_hit-to'] : $coord['hstop'];
      }
      else {
        $coord['hstop'] = $hsp['Hsp_hit-from'] - (($k + 1) * $wrap) + 1;
        $coord['hstop'] = ($coord['hstop'] < $hsp['Hsp_hit-to']) ? $hsp['Hsp_hit-to'] : $coord['hstop'];
      }

      // Pad these coordinates to ensure columned display.
      foreach ($coord as $ck => $val) {
        $pad_type = (preg_match('/start/', $ck)) ? STR_PAD_LEFT : STR_PAD_RIGHT;
        $coord[$ck] = str_pad($val, $coord_length, '#', $pad_type);
        $coord[$ck] = str_replace('#', '&nbsp', $coord[$ck]);
      }

      // Build the styled wrapped HTML.
      $wrapped .= '<div class="alignment-subrow">' . "\n";
      $wrapped .= '<span class="alignment-title">Query:</span>&nbsp;&nbsp;'
        . '<span class="alignment-start-coord";>' . $coord['qstart'] . '</span>&nbsp;'
        . '<span class="alignment-residues">' . $query[$k] . '</span>&nbsp;'
        . '<span class="alignment-stop-coord">' . $coord['qstop'] . "</span><br>\n";
      $wrapped .= str_repeat('&nbsp;', 8 + $coord_length + 1)
        . '<span class="alignment-residues">' . str_replace(' ', '&nbsp', $matches[$k]) . "</span><br>\n";
      $wrapped .= '<span class="alignment-title">Sbjct:</span>&nbsp;&nbsp;'
        . '<span class="alignment-start-coord";>' . $coord['hstart'] . '</span>&nbsp;'
        . '<span class="alignment-residues">' . $hit[$k] . '</span>&nbsp;'
        . '<span class="alignment-stop-coord">' . $coord['hstop'] . "</span><br>\n";
      $wrapped .= "</div>\n";
    }

    return $wrapped;
  }

}
