<?php

/**
 * @file
 * Provides Link-out functionality for BLAST hits.
 *
 * Specifically,
 */

/**
 * Implements hook_blast_linkout_info().
 *
 * Provide information on basic link-out types: link, GBrowse, JBrowse.
 */
function blast_ui_blast_linkout_info() {
  $types = array();

  $types['link'] = array(
    // Human-readable Type name to display to users in the BLAST Database
    // create/edit form.
    'name' => 'Generic Link',
    // The function used to generate the URL to be linked to.
    // This function will have full access to the blast hit and database
    // prefix information and is expected to return a URL.
    'process function' => 'tripal_blast_generate_linkout_link',
  );

  $types['gbrowse'] = array(
    'name' => 'GBrowse',
    'process function' => 'tripal_blast_generate_linkout_gbrowse',
  );

  $types['jbrowse'] = array(
    'name' => 'JBrowse',
    'process function' => 'tripal_blast_generate_linkout_jbrowse',
  );

  return $types;
}

/**
 * Generate a basic link-out for a given hit.
 *
 * Essentially, concatenate the URL prefix with the extracted hit identifier
 * and return the URL to be displayed by the BLAST report template.
 *
 * @param $url_prefix
 *   The URL prefix for the BLAST Database queried.
 * @param $hit
 *   The blast XML hit object. This object has the following keys based on the
 *   XML: Hit_num, Hit_id, Hit_def, Hit_accession, Hit_len and Hit_hsps.
 *   Furthermore, a linkout_id key has beek added that contains the part of the
 *   Hit_def extracted using a regex provided when the blastdb node was created.
 * @param $info
 *   Additional information that may be useful in creating a link-out. Includes:
 *    - query_name: the name of the query sequence.
 *    - score: the score of the blast hit.
 *    - e-value: the e-value of the blast hit.
 * @param $options
 *   Any additional options needed to determine the type of link-out. None are
 *   supported by this particular link-out type.
 *
 * @return
 *   The URL string to be linked to.
 */
function tripal_blast_generate_linkout_link($url_prefix, $hit, $info, $options = array()) {

  if (isset($hit->{'linkout_id'})) {
    return $url_prefix . $hit->{'linkout_id'};
  }
  else {
    return FALSE;
  }
}

/**
 * Generate a GBrowse link-out with location information for a given hit.
 *
 * NOTE: Assumes the hit is a backbone feature in the GBrowse linked to.
 *  Otherwise, the basic link can be used.
 *
 * @param $url_prefix
 *   The URL prefix for the BLAST Database queried.
 * @param $hit
 *   The blast XML hit object. This object has the following keys based on the
 *   XML: Hit_num, Hit_id, Hit_def, Hit_accession, Hit_len and Hit_hsps.
 *   Furthermore, a linkout_id key has beek added that contains the part of the
 *   Hit_def extracted using a regex provided when the blastdb node was created.
 * @param $info
 *   Additional information that may be useful in creating a link-out. Includes:
 *    - query_name: the name of the query sequence.
 *    - score: the score of the blast hit.
 *    - e-value: the e-value of the blast hit.
 * @param $options
 *   Any additional options needed to determine the type of link-out. None are
 *   supported by this particular link-out type.
 *
 * @return
 *   The URL string to be linked to.
 */
function tripal_blast_generate_linkout_gbrowse($url_prefix, $hit, $info, $options = array()) {

  $ranges = array();
  $coords = array();
  foreach($info['HSPs'] as $hsp) {
     array_push($ranges,$hsp['Hsp_hit-from'] . '..' . $hsp['Hsp_hit-to'] );
     array_push($coords,$hsp['Hsp_hit-from'] , $hsp['Hsp_hit-to'] );
   }
   $min = min($coords);
   $max = max($coords);
   $joined_ranges = join ("," , $ranges);
   $url_postfix = '&start=' . $min . '&stop='  . $max . '&add=' . $hit->{'hit_name'} . '+BLAST+' . $hit->{'hit_name'} . '_' . $info['query_name'] . '_' . $info['e-value'] . '+' . $joined_ranges ;

   return $url_prefix . $url_postfix;
}

/**
 * Generate a JBrowse link-out with location information for a given hit.
 *
 * NOTE: Assumes the hit is a backbone feature in the JBrowse linked to.
 *  Otherwise, the basic link can be used.
 *
 * @param $url_prefix
 *   The URL prefix for the BLAST Database queried.
 * @param $hit
 *   The blast XML hit object. This object has the following keys based on the
 *   XML: Hit_num, Hit_id, Hit_def, Hit_accession, Hit_len and Hit_hsps.
 *   Furthermore, the following keys have been added:
 *    -linkout_id: the part of the Hit_def extracted using a regex provided
 *     when the blastdb node was created.
 *    -hit_name: the name of the hit extracted in the template.
 * @param $info
 *   Additional information that may be useful in creating a link-out. Includes:
 *    - query_name: the name of the query sequence.
 *    - score: the score of the blast hit.
 *    - e-value: the e-value of the blast hit.
 * @param $options
 *   Any additional options needed to determine the type of link-out. None are
 *   supported by this particular link-out type.
 *
 * @return
 *   The URL string to be linked to.
 */
function tripal_blast_generate_linkout_jbrowse($url_prefix, $hit, $info, $options = array()) {

  $ranges = array();
  $coords = array();
  $hsps = array();
  foreach($info['HSPs'] as $hsp) {
   $hsp_start = $hsp['Hsp_hit-from'];
   $hsp_end = $hsp['Hsp_hit-to'] ;
   array_push($coords,$hsp['Hsp_hit-from'] , $hsp['Hsp_hit-to'] );
   array_push($ranges, '{"start":'.$hsp_start.',"end":'.$hsp_end.',"type":"match_part"}');
  }
  $min = min($coords);
  $max = max($coords);
  $url_postfix = '&addFeatures=[{"seq_id":"'.$hit->{'hit_name'}.'","score":"'.$info['e-value'].'","start":'.$min.',"end":'.$max.',"type":"match","name":"MyBLASTHit","subfeatures":[';
  $joined_ranges = join ("," , $ranges);
  $url_postfix = $url_postfix . $joined_ranges . ']}]&addTracks=[{"label":"BLAST","type":"JBrowse/View/Track/HTMLFeatures","store":"url"}]&loc='.$hit->{'hit_name'}.'&tracks=DNA%2CBLAST&highlight=';

  return $url_prefix . $url_postfix;
}