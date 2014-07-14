<?php

/**
 * @file
 * This file contains functions specifically related to theme-ing the BLAST module
 */

/**
 * Preprocess function for the show_blast_report.tpl.php
 *
 * Use this function to prepare variables for use in the template,
 * as well as to add css/javascript needed.
 *
 * @param $vars
 *   The variables currently available to the template.
 */
function blast_ui_preprocess_show_blast_report(&$vars) {

   $path = drupal_get_path('module', 'blast_ui');

   drupal_add_css($path . '/theme/blast_report.css');
   drupal_add_js('http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js');


  $path = current_path();
  if (preg_match('%blast/report/([\w\.]+)%',$path,$matches)) {
    $vars['xml_filename'] = 'sites/default/files/' . $matches[1];
  }

}
