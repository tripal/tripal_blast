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

  $url = false;
  $hit_name = $hit->{'Hit_def'};
//echo "hit name: $hit_name ... ";

  if ($info['Target'] == 'All genomes') {
    // default regex
    $regex = "/^\w+\.(\S+).*/";
    
    if (preg_match('/^aradu/', $hit_name) == 1) {
      $blastdb_name = 'PeanutBase_aradu_gbrowse';
    }
    else if (preg_match('/^araip/', $hit_name) == 1) {
      $blastdb_name = 'PeanutBase_araip_gbrowse';
    }
    else if (preg_match('/^cajca/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_cajca_gbrowse';
    }
    else if (preg_match('/^cicar/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_cicar_gbrowse';
    }
    else if (preg_match('/^glyma/', $hit_name) == 1) {
      $blastdb_name = 'SoyBase_glyma_gbrowse';
    }
    else if (preg_match('/^lotja/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_lotja_gbrowse';
    }
    else if (preg_match('/^medtr/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_medtr_gbrowse';
    }
    else if (preg_match('/^phavu/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_phavu_gbrowse';
    }
    else if (preg_match('/^vigra/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_vigra_gbrowse';
    }
    else {
      // Don't know what to do with this hit
      drupal_set_message("Don't know how to create GBrowse linkout for $hit_name", 'error');
    }  

    $hit_name = preg_replace($regex, '$1', $hit_name);
    $hit->{'linkout_id'} = $hit_name;
    if ($blastdb_info = tripal_custom_getBLASTdbinfo($blastdb_name)) {
//echo "generate gbrowse link with " . $blastdb_info['urlprefix'] . " $hit<br>";
      $url = tripal_blast_generate_linkout_gbrowse($blastdb_info['urlprefix'], $hit, $info, $options);
    }
  }//handle All genomes BLAST target
  
  else {
    // default regex:
    $regex = "/^\w+\.(\S+)/";
    
    if (preg_match('/^aradu/', $hit_name) == 1) {
      $blastdb_name = 'PeanutBase_aradu_gene';
    }
    else if (preg_match('/^araip/', $hit_name) == 1) {
      $blastdb_name = 'PeanutBase_araip_gene';
    }
    else if (preg_match('/^cajca/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_cajca_gene';
    }
    else if (preg_match('/^cicar/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_cicar_gene';
    }
    else if (preg_match('/^glyma/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_glyma_gene';
      if (preg_match("/^\w+\.(\S+)\.p/", $hit_name)) {
        //glyma protein
        $regex = "/^\w+\.(\S+)\.p/";
      }
    }
    else if (preg_match('/^lotja/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_lotja_gene';
    }
    else if (preg_match('/^medtr/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_medtr_gene';
    }
    else if (preg_match('/^phavu/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_phavu_gene';
    }
    else if (preg_match('/^vigra/', $hit_name) == 1) {
      $blastdb_name = 'LegumeInfo_vigra_gene';
    }
    else {
      // Don't know what to do with this hit
      drupal_set_message("Don't know how to create linkout for $hit_name", 'error');
    }  

    $hit_name = preg_match($regex, $hit_name, $matches);
//echo "<br>Using $regex, found matches:<pre>" . var_dump($matches);echo "</pre>";
//echo "<br>Use [" . $matches[1] . "]<br>";
    $hit->{'linkout_id'} = $matches[1];
//echo "look for gene link for $blastdb_name with " . $hit->{'linkout_id'} . "<br>";
    if ($blastdb_info = tripal_custom_getBLASTdbinfo($blastdb_name)) {
//echo "generate gbrowse link with " . $blastdb_info['urlprefix'] . " $hit<br>";
      $url = tripal_blast_generate_linkout_link($blastdb_info['urlprefix'], $hit, $info, $options);
    }
  }
  return $url;
}


function tripal_custom_getBLASTdbinfo($blastdb_name) {
  $sql = "
    SELECT urlprefix 
    FROM {db} WHERE name='$blastdb_name'";
//echo "$sql<br>";
  if ($res = chado_query($sql)) {
    if ($row=$res->fetchObject()) {
      return array(
        'urlprefix' => $row->urlprefix, 
      );
    }
  }
  
  return false;
}//tripal_custom_getBLASTdbinfo