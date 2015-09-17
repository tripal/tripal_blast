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
      $blastdb_name = 'PeanutBase_aradu_gbrowse';
    }
    else if (preg_match('/.*(araip).*/i', $hit_name) == 1) {
      $blastdb_name = 'PeanutBase_araip_gbrowse';
    }
    else if (preg_match('/^Cc/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_cajca_gbrowse';
    }
    else if (preg_match('/^Ca/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_cicar_gbrowse';
      $hit_name = preg_replace("/(.*?)\s.*/", '$1', $hit_name);
    }
    else if (preg_match('/^Gm/', $hit_name) == 1) {
      $blastdb_name = 'SoyBase_glyma_gbrowse';
    }
    else if (preg_match('/^Lj/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_lotja_gbrowse';
    }
    else if (preg_match('/^Mt/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_medtr_gbrowse';
    }
    else if (preg_match('/^Pv/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_phavu_gbrowse';
    }
    else if (preg_match('/^Vr/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_vigra_gbrowse';
    }

    $hit->{'linkout_id'} = $hit_name;
    $url_prefix = tripal_custom_getURLprefix($blastdb_name);
    $url = tripal_blast_generate_linkout_gbrowse($url_prefix, $hit, $info, $options);
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


function tripal_custom_getURLprefix($blastdb_name) {
  $sql = "SELECT urlprefix FROM db WHERE name='$blastdb_name'";
  if ($res = chado_query($sql)) {
    if ($row=$res->fetchObject()) {
      return $row->urlprefix;
    }
  }
  
  return false;
}//tripal_custom_getURLprefix