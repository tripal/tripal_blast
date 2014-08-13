<?php

/**
 * @file
 *
 */

/**
 *
 */
function blast_ui_per_query_type_form($form, $form_state) {

  // Determine the BLAST program.
  $query_type = $form_state['build_info']['args'][0];
  if ($query_type == 'nucleotide') {
    $blast_program = array(
      'blastn',
      'blastx'
    );
  }
  elseif ($query_type == 'protein') {
    $blast_program = array(
      'blastp',
      'tblastn'
    );
  }

  // Add the details about the specific BLAST choosen.
  $form['query_type'] = array(
    '#type' => 'hidden',
    '#value' => $query_type
  );
  $form['blast_program'] = array(
    '#type' => 'hidden',
    '#value' => implode(', ', $blast_program)
  );

  // Give the users a message.
  $form['msg'] = array(
    '#type' => 'item',
    '#markup' => 'COMMING SOON!'
  );

  return $form;
}