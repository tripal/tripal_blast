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

/**
 * Makes the tripal job_id unrecognizable.
 * 
 * @param $job_id
 *   The tripal job_id of the blast you want to make secret.
 *
 * @return
 *   A short string representing the job_id.
 */
function blast_ui_make_secret($job_id) {

  $mapping = blast_ui_secret_mapping();
  $secret = str_replace(array_keys($mapping), $mapping, $job_id);
  
  return $secret;
}

/**
 * Reveals the true job_id for your secret blast result.
 *
 * @param $secret
 *    The job_id previously made secret by blast_ui_make_secret().
 *
 * @return
 *    The revealed tripal job_id.
 */
function blast_ui_reveal_secret($secret) {

  $mapping = blast_ui_secret_mapping(TRUE);
  $job_id = str_replace(array_keys($mapping), $mapping, $secret);
  
  // Check that the job_id exists if it is an integer.
  if (is_integer($job_id)) {

    $exists = db_query('SELECT job_id FROM {tripal_jobs} WHERE job_id=:id', array(':id' => $job_id))->fetchField();
    if (!$exists) {
      tripal_report_error(
        'blast_ui',
        TRIPAL_ERROR,
        'Unable to decode the blast job_id from :id.',
        array(':id' => $secret)
      );
    }
    else {
      return $job_id;
    }
  }  
  // Last ditch effort: maybe this job was encoded before the upgrade?
  else {
  
    $job_id = base64_decode($secret);
    $exists = db_query('SELECT job_id FROM {tripal_jobs} WHERE job_id=:id', array(':id' => $job_id))->fetchField();
    if (!$exists) {
      tripal_report_error(
        'blast_ui',
        TRIPAL_ERROR,
        'Unable to decode the blast job_id from :id.',
        array(':id' => $secret)
      );
    }
    else {
      return $job_id;
    }
  }
  
  return FALSE;
}

/**
 * A single location for keeping track of the mapping used in our secrets.
 */
function blast_ui_secret_mapping($reveal = FALSE) {
  $mapping = array(
    1 => 'P',
    2 => 'sA',
    3 => 'b',
    4 => 'Q',
    5 => 'Hi',
    6 => 'yt',
    7 => 'f',
    8 => 'zE',
    9 => 'Km',
    0 => 'jVo',
  );
  
  // Since this is an open-source module with all the code publically available, 
  // our secret is not very secret... We are ok with this since the liklihood of 
  // profiting by stealing random blast results is pretty low. That said, if this bothers
  // you, feel free to implement the following function in a private module to change
  // this mapping to something that cannot easily be looked up on github. ;-).
  // NOTE: Ensure that the mapping you come up with is unique to ensure that the
  // job_id can be consistently revealed or your users might end up unable to find 
  // their own blast results...
  if (function_exists('private_make_mapping_ultra_secret')) {
    $mapping = private_make_mapping_ultra_secret($mapping);
  }
  
  if ($reveal) {
    return array_flip($mapping);
  }
  else {
    return $mapping;
  }
}

/**
 * Tests your secret mapping over a set of random integers 
 * to ensure the job_id can be recovered.
 *
 * @param $num_iterations
 *    An integer representing the number of times you wish to test your mapping.
 */
function blast_ui_test_secret_mapping($num_iterations = 10000) {

  $all_work = TRUE;
  
  for($i = 0; $i <= $num_iterations; $i++) {
    $job_id = mt_rand(0,100000);
    
    $secret = blast_ui_make_secret($job_id);
    $recovered_job_id = blast_ui_reveal_secret($secret);
    
    if ($job_id != $recovered_job_id) {
      drupal_set_message("Unable to recover job_id: $job_id; Secret: $secret.",'error');
      $all_work = FALSE;
    }
  }
  
  if ($all_work) {
    drupal_Set_message("Secret Mapping works over $num_iterations iterations with random integers.");
  }
  
}
