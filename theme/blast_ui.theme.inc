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
  
//eksc- could stand better use of module settings and fewer hardcoded paths.
  $vars['xml_filename'] = variable_get('file_public_path', conf_path() . '/files') . '/tripal/tripal_blast/' . $job_args['output_filename'] . '.blast.xml';
  $vars['tsv_filename'] = variable_get('file_public_path', conf_path() . '/files') . '/tripal/tripal_blast/' . $job_args['output_filename'] . '.blast.tsv';
  $vars['html_filename'] = variable_get('file_public_path', conf_path() . '/files') . '/tripal/tripal_blast/' . $job_args['output_filename'] . '.blast.html';

  // Add the blast database node.
  // This is needed for link-out functionality.
  $vars['blastdb'] = get_blast_database(array('path' => $job_args['database']));
  
  // Get the recent job information.
  $vars['recent_jobs'] = get_recent_blast_jobs();
  
  // Make job information available in the template.
  $vars['blast_job'] = $job;
  $job_data = variable_get('job_data', '');
  if (isset($job_data[ $vars['job_id'] ])) {
    $vars['blast_job']->form_options = $job_data[ $vars['job_id'] ];
  }
  if (isset($vars['recent_jobs'][ $vars['job_id'] ])) {
    $vars['blast_job']->display = $vars['recent_jobs'][ $vars['job_id'] ];
  }
  
  // Determine the blast command for display.
  $vars['blast_job']->display['blast_cmd'] = $vars['blast_job']->form_options['program'];
  foreach($vars['blast_job']->form_options['options'] as $key => $value) {
      $vars['blast_job']->display['blast_cmd'] .= ' -' . $key. ' ' . $value ;
  }
  
  // Load the XML file.
  $vars['xml'] = NULL;
  if (is_readable($vars['xml_filename'])) {
    $vars['xml'] = simplexml_load_file($vars['xml_filename']);
  }

}

/**
 * Implements hook_theme_registery_alter().
 */
function blast_ui_theme_registry_alter(&$theme_registry) {
  $theme_registry_copy = $theme_registry;
  $module_path = drupal_get_path('module', 'blast_ui') . '/theme';
  _theme_process_registry($theme_registry_copy, 'phptemplate', 'theme_engine', 'my_custom_theme', $module_path);
  $theme_registry += array_diff_key($theme_registry_copy, $theme_registry);
  // A list of templates the module will provide templates for
  $hooks = array('page');
  foreach ($hooks as $hook) {
    // Add the key 'theme paths' if it doesn't exist in this theme's registry
    if (!isset($theme_registry[$hook]['theme paths'])) {
      $theme_registry[$hook]['theme paths'] = array();
    }
    // Shift this module's directory to the top of the theme path list
    if (is_array($theme_registry[$hook]['theme paths'])) {
      $first_element = array_shift($theme_registry[$hook]['theme paths']);
      if ($first_element) {
        array_unshift($theme_registry[$hook]['theme paths'], $first_element, $module_path);
      }
      else {
        array_unshift($theme_registry[$hook]['theme paths'], $module_path);
      }
    }
  }
}