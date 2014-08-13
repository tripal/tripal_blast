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
    $template = 'blast_nucleotide_user_menupage';
    $blast_program = array(
      'blastn',
      'blastx'
    );
  }
  elseif ($query_type == 'protein') {
    $template = 'blast_protein_user_menupage';
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

  $form['msg'] = array(
    '#type' => 'item',
    '#markup' => theme($template)
  );

  return $form;
}