<?php
/**
 * @file
 * Contains class definition of Tripal BLAST Link-out service.
 */
namespace Drupal\tripal_blast\Services;

use Drupal\Core\Link;
use Drupal\Core\Url;

class TripalBlastLinkOut {
  
  function generateLink($url_prefix, $hitname) {
    
    if (isset($hitname)) {
      $hit_url = $url_prefix . $hitname;
      $link = Link::fromTextAndUrl($hitname, Url::fromUri($hit_url))->toRenderable();
      $link['#options']['attributes'] = [
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
      ];
      return $link;
    }
    else {
      return FALSE;
    }
  }
  
  
  function generateJbrowseLink($url_prefix, $hitname, $HSPs) {
    
    // First we need to collect the HSPs to define the ranges we want to
    // display on the JBrowse.
    $ranges = array();
    // We also keep track of all the coordinates in order to later
    // calculate the smallest and largest coordinate.
    $coords = array();
    $count = 0;
    foreach($HSPs as $hsp) {
      $count++;
      
      $strand = '1';
      $hsp_start = $hsp['Hsp_hit-from'];
      $hsp_end = $hsp['Hsp_hit-to'];
      
      // Handle alignments on the negative strand.
      if (($hsp_end - $hsp_start) < 0) {
        $strand = '-1';
        $hsp_start = $hsp['Hsp_hit-to'];
        $hsp_end = $hsp['Hsp_hit-from'];
      }
      
      // Add both the start & stop to the coordinate list.
      array_push($coords,$hsp['Hsp_hit-from'] , $hsp['Hsp_hit-to'] );
      
      // Format the hsp for inclusion in the subfeatures section of the track later.
      $hsp_def = t(
          '{"start":@start,"end":@end,"strand":"@strand","type":"@type"}',
          array(
            '@start' => $hsp_start,
            '@end' => $hsp_end,
            '@strand' => $strand,
            '@type' => 'match_part'
          )
          );
      array_push($ranges, $hsp_def);
    }
    // Calculate the minimum & maximum coordinates.
    $min = min($coords);
    $max = max($coords);
    
    // We also want some white-space on either side of out hit
    // when we show it in the JBrowse. To make this generic,
    // we want our blast hit to take up 2/3 of the screen thus
    // we have 1/6 per side for white-space.
    $buffer = round(($max - $min) / 6);
    $screen_start = $min - $buffer;
    $screen_end = $max + $buffer;
    
    // Now we are finally ready to build the URL.
    // First lets set the location of the hit so the JBrowse focuses in on the correct region.
    $jbrowse_query = array();
    $jbrowse_query['loc'] = t(
        'loc=@ref:@start..@stop',
        array(
          '@ref' => $hitname,
          '@start' => $screen_start,
          '@stop' => $screen_end,
        )
        );
    
    // Next we want to add our BLAST hit to the JBrowse.
    $jbrowse_query['addFeatures'] = t(
        'addFeatures=[{"seq_id":"@id","start":@min,"end":@max,"name":"@name","subfeatures":[@hspcoords]}]',
        array(
          '@id' => $hitname,
          '@name' => $info['query_name'] . ' Blast Hit',
          '@min' => $min,
          '@max' => $max,
          '@hspcoords' => join ("," , $ranges)
        ));
    
    // Then add a track to display our new feature.
    $jbrowse_query['addTracks'] = 'addTracks=[{"label":"blast","key":"BLAST Result","type":"JBrowse/View/Track/HTMLFeatures","store":"url"}]';
    
    $url_postfix = implode('&', $jbrowse_query);
    
    $hit_url = $url_prefix . $url_postfix;

    $link = Link::fromTextAndUrl($hitname, Url::fromUri($hit_url))->toRenderable();
    $link['#options']['attributes'] = [
      'target' => '_blank',
      'rel' => 'noopener noreferrer',
    ];
    return $link;
  }
}