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
  drupal_add_js('https://code.jquery.com/jquery-1.12.4.min.js');

  // Get blast job details.
  $vars['blast_job'] = get_BLAST_job($vars['job_id']);

  // Determine the blast command for display.
  $vars['blast_job']->blast_cmd = $vars['blast_job']->program;
  foreach($vars['blast_job']->options as $key => $value) {
      $vars['blast_job']->blast_cmd .= ' -' . $key. ' ' . $value ;
  }

  // CViTjs
  $vars['show_cvit_diagram'] = FALSE;
  if (variable_get('blast_ui_cvitjs_enabled', false)
    && isset($vars['blast_job']->blastdb->cvitjs_enabled)
    && $vars['blast_job']->blastdb->cvitjs_enabled == '1') {

    // Set a clean var so we don't have to do this long check again ;-).
    $vars['show_cvit_diagram'] = TRUE;

    // Add the CSS/JS.
    $cvitjs_lib_path = libraries_get_path('cvitjs') . DIRECTORY_SEPARATOR;
    drupal_add_css($cvitjs_lib_path . 'js/lib/bootstrap/css/bootstrap.min.css',array('preprocess'=>FALSE));
    drupal_add_css($cvitjs_lib_path . 'js/lib/hopscotch/css/hopscotch.min.css',array('preprocess'=>FALSE));
    drupal_add_css($cvitjs_lib_path . 'css/cvit.css',array('preprocess'=> FALSE));
    drupal_add_js($cvitjs_lib_path . 'js/lib/require/require.js',array('group'=>'JS_LIBRARY','type'=>'file'));
    drupal_add_js($cvitjs_lib_path . 'js/lib/require/blast_ui-config.js',array('group'=>'JS_THEME'));

    // Add the JS settings.
    global $base_url;
    drupal_add_js(array('blast_ui'=> array(
            'dataset' => $vars['blast_job']->blastdb->db_name,
            'gff' => $base_url . '/' . $vars['blast_job']->files->result->gff
      )),
      'setting'
    );
  }

  // Determine the URL of the blast form.
  $vars['blast_form_url'] = 'blast/nucleotide/nucleotide';
  switch($vars['blast_job']->program) {
    case 'blastn':
      $vars['blast_form_url'] = 'blast/nucleotide/nucleotide';
      break;
    case 'blastx':
      $vars['blast_form_url'] = 'blast/nucleotide/protein';
      break;
    case 'tblastn':
      $vars['blast_form_url'] = 'blast/protein/nucleotide';
      break;
    case 'blastp':
      $vars['blast_form_url'] = 'blast/protein/protein';
      break;
  }

  // Load the XML file.
  $vars['xml'] = NULL;
  $vars['num_results'] = FALSE;
  $vars['too_many_results'] = FALSE;

  $full_path_xml = DRUPAL_ROOT . DIRECTORY_SEPARATOR . $vars['blast_job']->files->result->xml;
  if (is_readable($full_path_xml)) {

    $vars['num_results'] = shell_exec('grep -c "<Hit>" ' . escapeshellarg($full_path_xml));

    $max_results = intval(variable_get('blast_ui_max_results_displayed', 500));
    if ($vars['num_results'] < $max_results) {
      $vars['xml'] = simplexml_load_file($full_path_xml);
    }
    else {
      $vars['too_many_results'] = TRUE;
    }
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
  if (is_numeric($job_id)) {

    $exists = db_query('SELECT job_id FROM {tripal_jobs} WHERE job_id=:id',
                       array(':id' => $job_id))->fetchField();
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
    if (is_numeric($job_id)) {
      $exists = db_query('SELECT job_id FROM {tripal_jobs} WHERE job_id=:id',
                         array(':id' => $job_id))->fetchField();
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
    else {
      tripal_report_error(
        'blast_ui',
        TRIPAL_ERROR,
        'Unable to decode the blast job_id from :id.',
        array(':id' => $secret)
      );
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
