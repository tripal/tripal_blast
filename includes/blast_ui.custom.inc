<?php
/*
 * customized for PeanutBase
 *
 */
 
function tripal_custom_generate_linkout($url_prefix, $hit, $info, $options = array()) {
// uncomment to see contents of hit object
//echo "hit:<pre>";var_dump($hit);echo "</pre>";
// uncomment to see contents of info object
//echo "info:<pre>";var_dump($info);echo "</pre>";

  $hit_name = $hit->{'Hit_def'};

  if ($info['Target'] == 'All genomes') {
    if (preg_match('/.*(aradu).*/i', $hit_name) == 1) {
      $hit->{'linkout_id'} = $hit_name;
      $url_prefix = "\gbrowse_aradu1.0?";   
      $url = tripal_blast_generate_linkout_gbrowse($url_prefix, $hit, $info, $options);
    }
    else if (preg_match('/.*(araip).*/i', $hit_name) == 1) {
      $hit->{'linkout_id'} = $hit_name;
      $url_prefix = "\gbrowse_araip1.0?";   
      $url = tripal_blast_generate_linkout_gbrowse($url_prefix, $hit, $info, $options);
    }
  }//handle All genomes BLAST target
  
  else {
    if (preg_match('/.*(aradu).*/i', $hit_name) == 1) {
      preg_match("/\w+\.(\w+\.\w+)/", $hit_name, $linkout_match);
      $linkout_id = $linkout_match[1];
      $hit->{'linkout_id'} = $linkout_id;
      $url =   "/gbrowse_aradu1.0?query=q=$linkout_id;h_feat=$linkout_id";
    }
    else if(preg_match('/.*(araip).*/i', $hit_name) == 1) {
      preg_match("/\w+\.(\w+\.\w+)/", $hit_name, $linkout_match);
      $linkout_id = $linkout_match[1];
      $hit->{'linkout_id'} = $linkout_id;
      $url =  "/gbrowse_araip1.0?query=q=$linkout_id;h_feat=$linkout_id";
    }
    else if(preg_match('/.*(phytozome).*/i', $hit_name) == 1) {
      preg_match("/(.*?)\s.*/", $hit_name, $linkout_match);
      $linkout_id = $linkout_match[1];
      $hit->{'linkout_id'} = $linkout_id;
      $url = "http://legumeinfo.org/chado_phylotree/$linkout_id";
    }
    else {
      // Don't know what to do with this hit
      drupal_set_message("Don't know how to create linkout for $hit_name", 'error');
      $url = false;
    }  
  }//handle remaining BLAST targets 
    
  return "$url";
}