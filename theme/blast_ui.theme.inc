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

  // Add CSS and Javascript files
  $path = drupal_get_path('module', 'blast_ui');
  drupal_add_css($path . '/theme/css/blast_report.css');
  drupal_add_js('http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js');

  // Get the filename of the BLAST results
  $job = tripal_get_job($vars['job_id']);
  $job_args = unserialize($job->arguments);
  $vars['xml_filename'] = variable_get('file_public_path', conf_path() . '/files') . '/' . $job_args['output_filename'] . '.blast.xml';
  $vars['tsv_filename'] = variable_get('file_public_path', conf_path() . '/files') . '/' . $job_args['output_filename'] . '.blast.tsv';
  $vars['html_filename'] = variable_get('file_public_path', conf_path() . '/files') . '/' . $job_args['output_filename'] . '.blast.html';

}
