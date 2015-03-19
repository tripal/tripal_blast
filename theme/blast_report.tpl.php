<?php

/**
 * Display the results of a BLAST job execution
 *
 * Variables Available in this template:
 *   $xml_filename: The full path & filename of XML file containing the BLAST results
 */

// Set ourselves up to do link-out if our blast database is configured to do so.
$linkout = FALSE;
if ($blastdb->linkout->none === FALSE) {
  $linkout = TRUE;
  $linkout_regex = $blastdb->linkout->regex;
  if (isset($blastdb->linkout->db_id->urlprefix) AND !empty($blastdb->linkout->db_id->urlprefix)) {
    $linkout_urlprefix = $blastdb->linkout->db_id->urlprefix;

    // Furthermore, check that we can determine the URL.
    // (ie: that the function specified to do so, exists).
    if (function_exists($blastdb->linkout->url_function)) {
      $url_function = $blastdb->linkout->url_function;
    }
    else {
      $linkout = FALSE;
    }
  }
  else {
    $linkout = FALSE;
  }
}

// Handle no hits. This following array will hold the names of all query
// sequences which didn't have any hits.
$query_with_no_hits = array();

// Furthermore, if no query sequences have hits we don't want to bother listing
// them all but just want to give a single, all-include "No Results" message.
$no_hits = TRUE;

?>

<!-- JQuery controlling display of the alignment information (hidden by default) -->
<script type="text/javascript">
  $(document).ready(function(){

    // Hide the alignment rows in the table
    // (ie: all rows not labelled with the class "result-summary" which contains the tabular
    // summary of the hit)
    $("#blast_report tr:not(.result-summary)").hide();
    $("#blast_report tr:first-child").show();

    // When a results summary row is clicked then show the next row in the table
    // which should be corresponding the alignment information
    $("#blast_report tr.result-summary").click(function(){
      $(this).next("tr").toggle();
      $(this).find(".arrow").toggleClass("up");
    });
  });
</script>

<style>
.no-hits-message {
  color: red;
  font-style: italic;
}
</style>

<p><strong>Download</strong>:
  <a href="<?php print '../../' . $html_filename; ?>">HTML</a>,
  <a href="<?php print '../../' . $tsv_filename; ?>">Tab-Delimited</a>,
  <a href="<?php print '../../' . $xml_filename; ?>">XML</a>
</p>

<p>The following table summarizes the results of your BLAST. To see additional information
about each hit including the alignment, click on that row in the table to expand it.</p>

<?php
include_once("blast_align_image.php");
// Load the XML file
$xml = simplexml_load_file($xml_filename);

/**
 * We are using the drupal table theme functionality to create this listing
 * @see theme_table() for additional documentation
 */

if ($xml) {
  // Specify the header of the table
  $header = array(
    'number' =>  array('data' => '#', 'class' => array('number')),
    'query' =>  array('data' => 'Query Name  (Click for alignment & visualization)', 'class' => array('query')),
    'hit' =>  array('data' => 'Hit Name', 'class' => array('hit')),
    'evalue' =>  array('data' => 'E-Value', 'class' => array('evalue')),
    'arrow-col' =>  array('data' => '', 'class' => array('arrow-col'))
  );

  $rows = array();
  $count = 0;

  // Parse the BLAST XML to generate the rows of the table
  // where each hit results in two rows in the table: 1) A summary of the query/hit and
  // significance and 2) additional information including the alignment
  foreach($xml->{'BlastOutput_iterations'}->children() as $iteration) {
    $children_count = $iteration->{'Iteration_hits'}->children()->count();
		//@deepaksomanadh: Code added for BLAST visualization
		// parameters that need to be passed for BLAST image generation
		$target_name = '';
		$q_name = $xml->{'BlastOutput_query-def'};
		$query_size = $xml->{'BlastOutput_query-len'};
		$target_size = $iteration->{'Iteration_stat'}->{'Statistics'}->{'Statistics_db-len'};
		
    if($children_count != 0) {
      foreach($iteration->{'Iteration_hits'}->children() as $hit) {
        if (is_object($hit)) {
          $count +=1;
          $zebra_class = ($count % 2 == 0) ? 'even' : 'odd';
          $no_hits = FALSE;

					// RETRIEVE INFO
          $hit_name = (preg_match('/BL_ORD_ID/', $hit->{'Hit_id'})) ? $hit->{'Hit_def'} : $hit->{'Hit_id'};	
					$score = $hit->{'Hit_hsps'}->{'Hsp'}->{'Hsp_score'};
					$evalue = $hit->{'Hit_hsps'}->{'Hsp'}->{'Hsp_evalue'};
				  $query_name = $iteration->{'Iteration_query-def'};

					// Round e-val to two decimal values
					$rounded_evalue = '';
					if (strpos($evalue,'e') != false) {
					 $evalue_split = explode('e', $evalue);
					 $rounded_evalue = round($evalue_split[0], 2, PHP_ROUND_HALF_EVEN);				    
						 $rounded_evalue .= 'e' . $evalue_split[1];
					}
					else { 
							$rounded_evalue = $evalue;
					}				
				
				  // ALIGNMENT ROW (collapsed by default)
					// Process HSPs
					// @deepaksomanadh: Code added for BLAST visualization
					// hit array and corresponding bit scores 
					// hits=4263001_4262263_1_742;4260037_4259524_895_1411;&scores=722;473;
					$HSPs = array();
					$hit_hsps = '';
					$hit_hsp_score = '';		
					foreach ($hit->{'Hit_hsps'}->children() as $hsp_xml) {
						$HSPs[] = (array) $hsp_xml;
		
						$hit_hsps .=  $hsp_xml->{'Hsp_hit-from'} . '_' . $hsp_xml->{'Hsp_hit-to'}  
														. '_' . $hsp_xml->{'Hsp_query-from'} . '_'
														. $hsp_xml->{'Hsp_query-to'} . ';';	
						$Hsp_bit_score .= 	$hsp_xml->{'Hsp_bit-score'} .';';							

					}	 
					// SUMMARY ROW
					// If the id is of the form gnl|BL_ORD_ID|### then the parseids flag
					// to makeblastdb did a really poor job. In this case we want to use
					// the def to provide the original FASTA header.
					
          // If our BLAST DB is configured to handle link-outs then use the
          // regex & URL prefix provided to create one.
          if ($linkout) {
            if (preg_match($linkout_regex, $hit_name, $linkout_match)) {

              $linkout_id = $linkout_match[1];
              $hit->{'linkout_id'} = $linkout_id;
              $hit->{'hit_name'} = $hit_name;

              $hit_url = call_user_func(
                $url_function,
                $linkout_urlprefix,
                $hit,
                array(
                  'query_name' => $query_name,
                  'score' => $score,
                  'e-value' => $evalue,
                  'HSPs' => $HSPs
                )
              );

              if ($hit_url) {
                $hit_name = l(
                  $linkout_id,
                  $hit_url,
                  array('attributes' => array('target' => '_blank'))
                );
              }
            }
          }

					//@deepaksomanadh: Code added for BLAST visualization
					// get the image and display
					$hit_img = generateImage($target_name, $Hsp_bit_score, $hit_hsps, $target_size, $query_size, $q_name);
				
					ob_start(); // Start buffering the output
					imagepng($hit_img, null, 0, PNG_NO_FILTER);
					$b64 = base64_encode(ob_get_contents()); // Get what we've just outputted and base64 it
					imagedestroy($hit_img);
					ob_end_clean();

					// Print the HTML tag with the image embedded
					$hit_img = '<h3> Hit Visualization </h3> <br><img src="data:image/png;base64,'.$b64.'"/>';
					
          $row = array(
            'data' => array(
              'number' => array('data' => $count, 'class' => array('number')),
              'query' => array('data' => $query_name, 'class' => array('query')),
              'hit' => array('data' => $hit_name, 'class' => array('hit')),
              'evalue' => array('data' => $rounded_evalue, 'class' => array('evalue')),
              'arrow-col' => array('data' => '<div class="arrow"></div>', 'class' => array('arrow-col'))
            ),
            'class' => array('result-summary')
          );
          $rows[] = $row;

          // ALIGNMENT ROW (collapsed by default)
          // Process HSPs

          $row = array(
            'data' => array(
              'number' => '',
              'query' => array(
                'data' => theme('blast_report_alignment_row', array('HSPs' => $HSPs)),
              ),
							'hit' => array(
                'data' => $hit_img,
                'colspan' => 3,
              ),
            ),
            'class' => array('alignment-row', $zebra_class),
            'no_striping' => TRUE
          );
          $rows[] = $row;

        }// end of if - checks $hit
      } //end of foreach - iteration_hits
    }	// end of if - check for iteration_hits
    else {

      // Currently where the "no results" is added.
      $query_name = $iteration->{'Iteration_query-def'};
      $query_with_no_hits[] = $query_name;

		} // end of else
  }

  if ($no_hits) {
    print '<p class="no-hits-message">No results found.</p>';
  }
  else {
    // We want to warn the user if some of their query sequences had no hits.
    if (!empty($query_with_no_hits)) {
      print '<p class="no-hits-message">Some of your query sequences did not '
      . 'match to the database/template. They are: '
      . implode(', ', $query_with_no_hits) . '.</p>';
    }

    // Actually print the table.
    if (!empty($rows)) {
      print theme('table', array(
        'header' => $header,
        'rows' => $rows,
        'attributes' => array('id' => 'blast_report'),
      ));
    }
  }
}
else {
  drupal_set_title('BLAST: Error Encountered');
  print '<p>We encountered an error and are unable to load your BLAST results.</p>';
}
?>
