<?php

/**
 * @file
 * Contains helper functions related to program-specific advanced options.
 */

/**
 * @section
 * BLASTn: Search a nucleotide database using a nucleotide query.
 * ----------------------------------------------------------------------------
 */

/**
 * Adds the BLASTn Advanced Options to the passed in form.
 *
 * This form function is meant to be called within another form definition.
 *
 * @param $form
 *   The form the advanced options should be added to. This form already
 *   contains a $form['ALG'] fieldset meant to contain the advanced options.
 * @param $formstate
 *   The current state fo the form passed in as $form.
 */
function blast_ui_blastn_advanced_options_form(&$form, $form_state) {
  
  // Edit and Resubmit functionality.
  // We want to pull up the details from a previous blast and fill them in as defaults
  // for this blast.
  if (isset($form_state['prev_blast'])) {
    $defaults = _get_default_values($form_state['prev_blast']->options, 'blastn');
  }
  else {
    $defaults = _get_default_values(array(), 'blastn');
  }
  
  // General parameters
  //.........................

  $form['ALG']['GParam'] = array(
   '#type' => 'fieldset',
   '#title' => t('General parameters'),
   '#collapsible' => FALSE,
  );

  $form['ALG']['GParam']['maxTarget'] = array(
    '#type' => 'select',
    '#title' => t('Max target sequences:'),
    '#options' => _get_max_target('blastn'),
    '#default_value' => $defaults['max_target_seqs'],
    '#description' => t('Select the maximum number of aligned sequences to display'),
  );

/*eksc- remove until we learn how this is implemented by NCBI
  $form['ALG']['GParam']['shortQueries'] = array(
    '#type' => 'checkbox',
    '#title' => t('Automatically adjust parameters for short input sequences'),
    '#default_value' => $short_queries,
  );
*/

  $form['ALG']['GParam']['eVal'] = array(
    '#type' => 'textfield',
    '#title' => t('e-Value (Expected Threshold)'),
    '#default_value' => $defaults['evalue'],
    '#size' => 12,
    '#maxlength' => 20,
    '#description' => t('Expected number of chance matches in a random model. This number should be give in a decimal format. <a href="https://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml#expect" target="_blank">More Information</a> | <a href="https://www.youtube.com/watch?v=nO0wJgZRZJs" target="_blank">Expect value video tutorial</a>'),
  );

  $form['ALG']['GParam']['wordSize'] = array(
    '#type' => 'select',
    '#title' => t('Word size:'),
    '#options' => _get_word_size('blastn'),
    '#default_value' => $defaults['word_size'],
    '#description' => t('The length of the seed that initiates an alignment'),
  );

  $form['ALG']['GParam']['qRange'] = array(
    '#type' => 'textfield',
    '#title' => t('Max matches in a query range'),
    '#default_value' => $defaults['qRange'],
    '#size' => 12,
    '#maxlength' => 20,
    '#description' => t('Limit the number of matches to a query range. This option is useful if many strong matches to one part of a query may prevent BLAST from presenting weaker matches to another part of the query.'),
  );

  // Scoring parameters
  //.........................

  $form['ALG']['SParam'] = array(
    '#type' => 'fieldset',
    '#title' => t('Scoring parameters'),
    '#collapsible' => FALSE,
  );

  $form['ALG']['SParam']['M&MScores'] = array(
    '#type' => 'select',
    '#title' => t('Match/Mismatch Scores:'),
    '#options' => _get_match_mismatch('blastn'),
    '#default_value' => $defaults['matchmiss'],
    '#description' => t('Reward and penalty for matching and mismatching bases.'),
   );

   $form['ALG']['SParam']['gapCost'] = array(
    '#type' => 'select',
    '#title' => t('Gap Costs:'),
    '#options' => _get_gap('blastn'),
    '#default_value' => $defaults['gap'],
    '#description' => t('Cost to create and extend a gap in an alignment. Linear costs are available only with megablast and are determined by the match/mismatch scores.'),
  );
}

/**
 * Validate the advanced options provided by the BLASTn form above.
 *
 * @see blast_ui_blastn_advanced_options_form().
 */
function blast_ui_blastn_advanced_options_form_validate($form, $form_state) { }

/**
 * Processed the advanced options provided by the BLASTn form above.
 *
 * @see blast_ui_blastn_advanced_options_form().
 */
function blast_ui_blastn_advanced_options_form_submit($form, $form_state) {
  $eVal = $form_state['values']['eVal'];

  $trgtKey = $form_state['values']['maxTarget'];
  $numAlign = $form['ALG']['GParam']['maxTarget']['#options'][$trgtKey];
  
  $wsKey = $form_state['values']['wordSize'];
  $wordSize = $form['ALG']['GParam']['wordSize']['#options'][$wsKey];
  
  // Expand Gap Cost key into open and extend penalties
  $gap = _set_gap($form_state['values']['gapCost']);

  // Expand Match/Mismatch option into penalty/reward values
  $m_m = _set_match_mismatch($form_state['values']['M&MScores']);

  // Limit number of query hits
  $qRange = $form_state['values']['qRange'];

  return array(
  	'max_target_seqs' => $numAlign,
    'evalue'          => $eVal,
    'word_size'       => $wordSize,
    'gapopen'         => $gap['gapOpen'],
    'gapextend'       => $gap['gapExtend'],
    'penalty'         => $m_m['penalty'],
    'reward'          => $m_m['reward'],
    'culling_limit'   => $qRange,
  );
}

/**
 * @section
 * BLASTx: Search protein database using a translated nucleotide query.
 * ----------------------------------------------------------------------------
 */

/**
 * Adds the BLASTx Advanced Options to the passed in form.
 *
 * This form function is meant to be called within another form definition.
 *
 * @param $form
 *   The form the advanced options should be added to. This form already
 *   contains a $form['ALG'] fieldset meant to contain the advanced options.
 * @param $formstate
 *   The current state fo the form passed in as $form.
 */
function blast_ui_blastx_advanced_options_form(&$form, $form_state) {

  // Edit and Resubmit functionality.
  // We want to pull up the details from a previous blast and fill them in as defaults
  // for this blast.
  if (isset($form_state['prev_blast'])) {
    $defaults = _get_default_values($form_state['prev_blast']->options, 'blastn');
  }
  else {
    $defaults = _get_default_values(array(), 'blastx');
  }

   $form['ALG']['GParam'] = array(
   '#type' => 'fieldset',
   '#title' => t('General parameters'),
   '#collapsible' => FALSE,
  );

  $form['ALG']['GParam']['maxTarget'] = array(
    '#type' => 'select',
    '#title' => t('Max target sequences:'),
    '#options' => _get_max_target('blastx'),
    '#default_value' => $defaults['max_target_seqs'],
    '#description' => t('Select the maximum number of aligned sequences to display'),
  );

  $form['ALG']['GParam']['eVal'] = array(
    '#type' => 'textfield',
    '#title' => t('e-Value (Expected Threshold)'),
    '#default_value' => $defaults['evalue'],
    '#size' => 12,
    '#maxlength' => 20,
    '#description' => t('Expected number of chance matches in a random model. This number should be give in a decimal format. <a href="https://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml#expect" target="_blank">More Information</a> | <a href="https://www.youtube.com/watch?v=nO0wJgZRZJs" target="_blank">Expect value vedio tutorial</a>'),
  );

/*eksc- need to learn how this is implemented for blastx
  $form['ALG']['GParam']['shortQueries'] = array(
    '#type' => 'checkbox',
    '#title' => t('Automatically adjust parameters for short input sequences'),
    '#default_value' => TRUE,
  );
*/

  $form['ALG']['GParam']['wordSize'] = array(
    '#type' => 'select',
    '#title' => t('Word size:'),
    '#options' => _get_word_size('blastx'),
    '#default_value' => $defaults['word_size'],
    '#description' => t('The length of the seed that initiates an alignment'),
  );

  // Scoring parameters
  //.........................

  $form['ALG']['SParam'] = array(
    '#type' => 'fieldset',
    '#title' => t('Scoring parameters'),
    '#collapsible' => FALSE,
  );

  $matrix_options = _get_matrix_options();
  $form['ALG']['SParam']['Matrix'] = array(
    '#type' => 'select',
    '#title' => 'Matrix',
    '#options' => $matrix_options,
    '#default_value' => $defaults['matrix'],
    '#description' => t('Assigns a score for aligning pairs of residues, and determines overall alignment score..'),
    '#ajax' => array(
      'callback' => 'ajax_dependent_dropdown_callback',
      'wrapper' => 'dropdown-second-replace',
    ),
  );

  $form['ALG']['GParam']['qRange'] = array(
    '#type' => 'textfield',
    '#title' => t('Max matches in a query range'),
    '#default_value' => $defaults['qRange'],
    '#size' => 12,
    '#maxlength' => 20,
    '#description' => t('Limit the number of matches to a query range. This option is useful if many strong matches to one part of a query may prevent BLAST from presenting weaker matches to another part of the query.'),
  );

/*eksc- NOT match/mismatch but instead computational adjustments; 
        need to learn how there are implemented for blastx
  $form['ALG']['SParam']['M&MScores'] = array(
    '#type' => 'select',
    '#title' => t('Match/Mismatch Scores:'),
    '#options' => array(
				0 => t('No adjustment'),
				1 => t('Composition-based statistics'),
				2 => t('Conditional compositional score matrix adjustment'),
				3 => t('Universal composition score matrix adjustment '),
			),
    '#default_value' => 2,
    '#description' => t('Matrix adjustment method to compensate for amino acid composition of sequences'),
  );
*/
}

/**
 * Validate the advanced options provided by the BLASTn form above.
 *
 * @see blast_ui_blastx_advanced_options_form().
 */
function blast_ui_blastx_advanced_options_form_validate($form, $form_state) { }

/**
 * Processed the advanced options provided by the BLASTx form above.
 *
 * @see blast_ui_blastx_advanced_options_form().
 */
function blast_ui_blastx_advanced_options_form_submit($form, $form_state) {

  // Same as blastp form submit
  return blast_ui_blastp_advanced_options_form_submit($form, $form_state);

}

/**
 * @section
 * BLASTp: Search protein database using a protein query.
 * ----------------------------------------------------------------------------
 */

/**
 * Adds the BLASTp Advanced Options to the passed in form.
 *
 * This form function is meant to be called within another form definition.
 *
 * @param $form
 *   The form the advanced options should be added to. This form already
 *   contains a $form['ALG'] fieldset meant to contain the advanced options.
 * @param $formstate
 *   The current state fo the form passed in as $form.
 */
function blast_ui_blastp_advanced_options_form(&$form, $form_state) {

  // Edit and Resubmit functionality.
  // We want to pull up the details from a previous blast and fill them in as defaults
  // for this blast.
  if (isset($form_state['prev_blast'])) {
    $defaults = _get_default_values($form_state['prev_blast']->options, 'blastn');
  }
  else {
    $defaults = _get_default_values(array(), 'blastp');
  }

  //General parameters

  $form['ALG']['GParam'] = array(
    '#type' => 'fieldset',
    '#title' => t('General parameters'),
    '#collapsible' => FALSE,
  );

  $form['ALG']['GParam']['maxTarget'] = array(
    '#type' => 'select',
    '#title' => t('Max target sequences:'),
    '#options' => _get_max_target('blastp'),
    '#default_value' => $defaults['max_target_seqs'],
    '#description' => t('Select the maximum number of aligned sequences to display'),
  );

/*eksc- remove until we learn how this is implemented
  $form['ALG']['GParam']['shortQueries'] = array(
   '#type' => 'checkbox',
   '#title' => t('Automatically adjust parameters for short input sequences'),
   '#default_value' => TRUE,
  );
*/

  $form['ALG']['GParam']['eVal'] = array(
    '#type' => 'textfield',
    '#title' => t('e-value(Expect threshold)'),
    '#default_value' => $defaults['evalue'],
    '#size' => 12,
    '#maxlength' => 20,
    '#description' => t('Expected number of chance matches in a random model.'),
  );

  $form['ALG']['GParam']['wordSize'] = array(
    '#type' => 'select',
    '#title' => t('Word size:'),
    '#options' => _get_word_size('blastp'),
    '#default_value' => $defaults['word_size'],
    '#description' => t('The length of the seed that initiates an alignment'),
  );

  $form['ALG']['GParam']['qRange'] = array(
    '#type' => 'textfield',
    '#title' => t('Max matches in a query range'),
    '#default_value' => $defaults['qRange'],
    '#size' => 12,
    '#maxlength' => 20,
    '#description' => t('Limit the number of matches to a query range. This option is useful if many strong matches to one part of a query may prevent BLAST from presenting weaker matches to another part of the query.'),
  );

  // Scoring parameters

  $form['ALG']['SParam'] = array(
   '#type' => 'fieldset',
   '#title' => t('Scoring parameters'),
   '#collapsible' => FALSE,
  );

  $matrix_options = _get_matrix_options();
  $form['ALG']['SParam']['Matrix'] = array(
    '#type' => 'select',
    '#title' => 'Matrix',
    '#options' => $matrix_options,
    '#default_value' => $defaults['matrix'],
    '#description' => t('Assigns a score for aligning pairs of residues, and determines overall alignment score..'),
    '#ajax' => array(
      'callback' => 'ajax_dependent_dropdown_callback',
      'wrapper' => 'dropdown-second-replace',
    ),
  );

/*eksc- probably not used for blastp
  $form['ALG']['SParam']['gapCost'] = array(
    '#type' => 'select',
    '#title' => t('Gap Costs:'),
    '#prefix' => '<div id="dropdown-second-replace">',
    '#suffix' => '</div>',
    '#options' => _get_gap_for_matrix($selected),
    '#default_value' => 2,
    '#description' => t('Cost to create and extend a gap in an alignment.'),
  );
*/

/*eksc- NOT match/mismatch but instead computational adjustments; 
        need to learn how there are implemented for blastp
  $form['ALG']['SParam']['M&MScores'] = array(
    '#type' => 'select',
    '#title' => t('Match/Mismatch Scores:'),
    '#options' => array(
      0 => t('No adjustment'),
      1 => t('Composition-based statistics'),
      2 => t('Conditional compositional score matrix adjustment'),
      3 => t('Universal composition score matrix adjustment '),
    ),
    '#default_value' => 2,
    '#description' => t('Matrix adjustment method to compensate for amino acid composition of sequences'),
  );
*/
}

/**
 * Validate the advanced options provided by the BLASTp form above.
 *
 * @see blast_ui_blastp_advanced_options_form().
 */
function blast_ui_blastp_advanced_options_form_validate($form, $form_state) { }

/**
 * Processed the advanced options provided by the BLASTp form above.
 *
 * @see blast_ui_blastp_advanced_options_form().
 */
function blast_ui_blastp_advanced_options_form_submit($form, $form_state) {

  $eVal = $form_state['values']['eVal'];

  $trgtKey = $form_state['values']['maxTarget'];
  $numAlign = $form['ALG']['GParam']['maxTarget']['#options'][$trgtKey];

  $wsKey = $form_state['values']['wordSize'];
  $wordSize = $form['ALG']['GParam']['wordSize']['#options'][$wsKey];

  $qRange = $form_state['values']['qRange'];
  
  // Expand Gap Cost key into open and extend penalties
  $matrix = $form_state['values']['Matrix'];
  $gapKey = $form_state['values']['gapCost'];
  $gap    = getGap( $matrix, $gapKey);
      
    

   
//eksc- need to implement query range limit
//  q_range

  return array(
  	'max_target_seqs' => $numAlign,
    'evalue'          => $eVal,
    'word_size'       => $wordSize,
    'gapopen' => $gap[ 'gapOpen' ],
    'gapextend' => $gap[ 'gapExtend' ],
    'culling_limit'   => $qRange,
    'matrix'          => $matrix,
  );
}//blast_ui_blastp_advanced_options_form_submit

/**
 * Fill the matrix dropdown list with appropriate options
 *
 * @return
 * An array consisting of matrices name for the first dropdown list
 */
function _get_matrix_options() {
  return drupal_map_assoc(array(
		t('PAM30'),
		t('PAM70'),
		t('PAM250'),
		t('BLOSUM80'),
		t('BLOSUM62'),
		t('BLOSUM45'),
		t('BLOSUM50'),
		t('BLOSUM90'),
  ));
}

/**
 * Fill the gap penalty dropdown list with appropriate options given selected matrix
 *
 * @return
 * An array containing open and extension gap values for the chosen matrix (to fill the second dropdown list)
 */
function _get_gap_for_matrix($key = '') {
  $options = array(
    t('PAM30') => drupal_map_assoc(array(
      t('Existence: 7 Extension: 2'),
      t('Existence: 6 Extension: 2'),
      t('Existence: 5 Extension: 2'),
      t('Existence: 10 Extension: 1'),
      t('Existence: 9 Extension: 1'),
      t('Existence: 8 Extension: 1'),
     )),
    t('PAM70') => drupal_map_assoc(array(
      t('Existence: 8 Extension: 2'),
      t('Existence: 7 Extension: 2'),
      t('Existence: 6 Extension: 2'),
      t('Existence: 11 Extension: 1'),
      t('Existence: 10 Extension: 1'),
      t('Existence: 9 Extension: 1'),
    )),
    t('PAM250') => drupal_map_assoc(array(
      t('Existence: 15 Extension: 3'),
      t('Existence: 14 Extension: 3'),
      t('Existence: 13 Extension: 3'),
      t('Existence: 12 Extension: 3'),
      t('Existence: 11 Extension: 3'),
      t('Existence: 17 Extension: 2'),
      t('Existence: 16 Extension: 2'),
      t('Existence: 15 Extension: 2'),
      t('Existence: 14 Extension: 2'),
      t('Existence: 13 Extension: 2'),
      t('Existence: 21 Extension: 1'),
      t('Existence: 20 Extension: 1'),
      t('Existence: 19 Extension: 1'),
      t('Existence: 18 Extension: 1'),
      t('Existence: 17 Extension: 1'),
    )),
    t('BLOSUM80') => drupal_map_assoc(array(
      t('Existence: 8 Extension: 2'),
      t('Existence: 7 Extension: 2'),
      t('Existence: 6 Extension: 2'),
      t('Existence: 11 Extension: 1'),
      t('Existence: 10 Extension: 1'),
      t('Existence: 9 Extension: 1'),
    )),
    t('BLOSUM62') => drupal_map_assoc(array(
      t('Existence: 11 Extension: 2'),
      t('Existence: 10 Extension: 2'),
      t('Existence: 9 Extension: 2'),
      t('Existence: 8 Extension: 2'),
      t('Existence: 7 Extension: 2'),
      t('Existence: 6 Extension: 2'),
      t('Existence: 13 Extension: 1'),
      t('Existence: 12 Extension: 1'),
      t('Existence: 11 Extension: 1'),
      t('Existence: 10 Extension: 1'),
      t('Existence: 9 Extension: 1'),
    )),
    t('BLOSUM45') => drupal_map_assoc(array(
      t('Existence: 13 Extension: 3'),
      t('Existence: 12 Extension: 3'),
      t('Existence: 11 Extension: 3'),
      t('Existence: 10 Extension: 3'),
      t('Existence: 15 Extension: 2'),
      t('Existence: 14 Extension: 2'),
      t('Existence: 13 Extension: 2'),
      t('Existence: 12 Extension: 2'),
      t('Existence: 19 Extension: 1'),
      t('Existence: 18 Extension: 1'),
      t('Existence: 17 Extension: 1'),
      t('Existence: 16 Extension: 1'),
    )),
    t('BLOSUM50') => drupal_map_assoc(array(
      t('Existence: 13 Extension: 3'),
      t('Existence: 12 Extension: 3'),
      t('Existence: 11 Extension: 3'),
      t('Existence: 10 Extension: 3'),
      t('Existence: 9 Extension: 3'),
      t('Existence: 16 Extension: 2'),
      t('Existence: 15 Extension: 2'),
      t('Existence: 14 Extension: 2'),
      t('Existence: 13 Extension: 2'),
      t('Existence: 12 Extension: 2'),
      t('Existence: 19 Extension: 1'),
      t('Existence: 18 Extension: 1'),
      t('Existence: 17 Extension: 1'),
      t('Existence: 16 Extension: 1'),
      t('Existence: 15 Extension: 1'),
    )),
    t('BLOSUM90') => drupal_map_assoc(array(
      t('Existence: 9 Extension: 2'),
      t('Existence: 8 Extension: 2'),
      t('Existence: 7 Extension: 2'),
      t('Existence: 6 Extension: 2'),
      t('Existence: 11 Extension: 1'),
      t('Existence: 10 Extension: 1'),
      t('Existence: 9 Extension: 1'),
    )),
  );
  
	if (isset($options[$key])) {
		return $options[$key];
	} 
	else {
		return array();
	}
}//_get_gap_for_matrix

/**
 * Respond to Ajax dropdown call
 */
function ajax_dependent_dropdown_callback($form, $form_state) {
  return $form['ALG']['SParam']['gapCost'];
}

/**
 * @section
 * tBLASTn: Search translated nucleotide database using a protein query.
 * ----------------------------------------------------------------------------
 */

/**
 * Adds the tBLASTn Advanced Options to the passed in form.
 *
 * This form function is meant to be called within another form definition.
 *
 * @param $form
 *   The form the advanced options should be added to. This form already
 *   contains a $form['ALG'] fieldset meant to contain the advanced options.
 * @param $formstate
 *   The current state fo the form passed in as $form.
 */
function blast_ui_tblastn_advanced_options_form(&$form, $form_state) {

  // Edit and Resubmit functionality.
  // We want to pull up the details from a previous blast and fill them in as defaults
  // for this blast.
  if (isset($form_state['prev_blast'])) {
    $defaults = _get_default_values($form_state['prev_blast']->options, 'blastn');
  }
  else {
    $defaults = _get_default_values(array(), 'tblastn');
  }

  $form['ALG']['GParam'] = array(
   '#type' => 'fieldset',
   '#title' => t('General parameters'),
   '#collapsible' => FALSE,
  );

  $form['ALG']['GParam']['maxTarget'] = array(
    '#type' => 'select',
    '#title' => t('Max target sequences:'),
    '#options' => _get_max_target('tblastn'),
    '#default_value' => $defaults['max_target_seqs'],
    '#description' => t('Select the maximum number of aligned sequences to display'),
  );

  $form['ALG']['GParam']['eVal'] = array(
    '#type' => 'textfield',
    '#title' => t('e-Value (Expected Threshold)'),
    '#default_value' => $defaults['evalue'],
    '#size' => 12,
    '#maxlength' => 20,
    '#description' => t('Expected number of chance matches in a random model. This number should be give in a decimal format. <a href="https://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml#expect" target="_blank">More Information</a> | <a href="https://www.youtube.com/watch?v=nO0wJgZRZJs" target="_blank">Expect value vedio tutorial</a>'),
  );

/*eksc- need to learn how this is implemented for tblastn
  $form['ALG']['GParam']['shortQueries'] = array(
    '#type' => 'checkbox',
    '#title' => t('Automatically adjust parameters for short input sequences'),
    '#default_value' => TRUE,
  );
*/

  $form['ALG']['GParam']['wordSize'] = array(
    '#type' => 'select',
    '#title' => t('Word size:'),
    '#options' => _get_word_size('tblastn'),
    '#default_value' => $defaults['word_size'],
    '#description' => t('The length of the seed that initiates an alignment'),
  );

  // Scoring parameters
  //.........................

  $form['ALG']['SParam'] = array(
    '#type' => 'fieldset',
    '#title' => t('Scoring parameters'),
    '#collapsible' => FALSE,
  );

  $matrix_options = _get_matrix_options();
  $form['ALG']['SParam']['Matrix'] = array(
    '#type' => 'select',
    '#title' => 'Matrix',
    '#options' => $matrix_options,
    '#default_value' => $defaults['matrix'],
    '#description' => t('Assigns a score for aligning pairs of residues, and determines overall alignment score..'),
    '#ajax' => array(
      'callback' => 'ajax_dependent_dropdown_callback',
      'wrapper' => 'dropdown-second-replace',
    ),
  );

  $form['ALG']['SParam']['gapCost'] = array(
    '#type' => 'select',
    '#title' => t('Gap Costs:'),
    '#prefix' => '<div id="dropdown-second-replace">',
    '#suffix' => '</div>',
    '#options' => _get_gap_for_matrix($defaults['matrix']),
    '#default_value' => 2,
    '#description' => t('Cost to create and extend a gap in an alignment.'),
  );

  $form['ALG']['GParam']['qRange'] = array(
    '#type' => 'textfield',
    '#title' => t('Max matches in a query range'),
    '#default_value' => $defaults['qRange'],
    '#size' => 12,
    '#maxlength' => 20,
    '#description' => t('Limit the number of matches to a query range. This option is useful if many strong matches to one part of a query may prevent BLAST from presenting weaker matches to another part of the query.'),
  );
}

/**
 * Validate the advanced options provided by the tBLASTn form above.
 *
 * @see blast_ui_tblastn_advanced_options_form().
 */
function blast_ui_tblastn_advanced_options_form_validate($form, $form_state) { }

/**
 * Processed the advanced options provided by the tBLASTn form above.
 *
 * @see blast_ui_tblastn_advanced_options_form().
 */
function blast_ui_tblastn_advanced_options_form_submit($form, $form_state) {

  return blast_ui_blastp_advanced_options_form_submit($form, $form_state);

}

/**
 * Get default form values; may come from saved job data if user is re-running
 *   a previous job.
 */
function _get_default_values($options) {
  // restore previous values or set to default
  $max_target = (isset($options['max_target_seqs'])) 
  					? $options['max_target_seqs'] : 10;
  $short_queries = (isset($options['shortQueries'])) 
  					? $options['shortQueries'] : true;
  $evalue = (isset($options['evalue'])) 
  					? $options['evalue'] : .001;
  $word_size = (isset($options['word_size'])) 
  					? $options['word_size'] : 11;
  $qRange = (isset($options['culling_limit'])) 
  					? $options['culling_limit'] : 0;

  $matchmiss = 0;
  $reward = (isset($options['reward'])) 
  					? $options['reward'] : 1;
  $penalty = (isset($options['penalty'])) 
  					? $options['penalty'] : -2;
  if ($reward == 1) {
  	switch ($penalty) {
  		case -1: $matchmiss = 5; break;
  	  case -2: $matchmiss = 0; break;
  	  case -3: $matchmiss = 1; break;
  	  case -4: $matchmiss = 2; break;
  	}
  }
  else if ($reward == 2) {
  	$matchmiss = 3;
  }
  else if ($reward == 3) {
  	 $matchmis = 4;
  }
  else if ($eward == 4) {
  	 $matchmiss = 5;
  }
  
  $gap = 0;
  $gapopen = (isset($options['gapopen'])) 
  					? $options['gapopen'] : 5;
  $gapextend = (isset($options['gapextend'])) 
  					? $options['gapextend'] : 2;
  if ($gapextend == 2) {
  	switch ($gapopen) {
  		case 5: $gap = 0; break;
  		case 2: $gap = 1; break;
  		case 1: $gap = 2; break;
  		case 0: $gap = 3; break;
  	}
  }
  else if ($gapextend == 1) {
  	switch ($gapopen) {
  		case 3: $gap = 4;
  		case 2: $gap = 5;
  		case 1: $gap = 6;
  	}
  }
  
// eksc- need to implement query range limit
//  $q_range = 0;
  
  $matrix = (isset($options['matrix'])) 
  					? $options['matrix'] : 'PAM30';
  return array(
  	'max_target_seqs' => $max_target,
  	'short_queries'   => $short_queries,
  	'word_size'       => $word_size,
  	'evalue'          => $evalue,
  	'matchmiss'       => $matchmiss,
  	'gap'             => $gap,
  	'qRange'          => $qRange,
    'matrix'          => $matrix,
  );
}//_get_default_values

/**
 * Get a list of options for the max_target_seq blast option.
 *
 * The options are the same for all programs
 * and describe the maximum number of aligned sequences to keep.
 */
function _get_max_target($which) {
	switch ($which) {
	  case 'blastn':
	  case 'blastx':
	  case 'blastp':
	  case 'tblastn':
			return array(
				10 => t('10'),
				50 => t('50'),
				100 => t('100'),
				250 => t('250'),
				500 => t('500'),
				1000 => t('1000'),
				5000 => t('5000'),
				10000 => t('10000'),
				20000 => t('20000'),
			);
	}//switch
}

/**
 * Get a list of options for work size.
 */
function _get_word_size($which) {
	switch ($which) {
		case 'blastn':
			 return array(
				 7 => t('7'),  
				 11 => t('11'),
				 15 => t('15'),
				 16 => t('16'),
				 20 => t('20'),
				 24 => t('24'),
				 28 => t('28'),
				 32 => t('32'),
				 48 => t('48'),
				 64 => t('64'),
			 	 128 => t('128'),
				 256 => t('256'),
	    );
    case 'blastx':
    case 'blastp':
    case 'tblastn':
    	return array(
      //  2 => t('2'),
        3 => t('3'),
        6 => t('6'),
      );
	}//switch
}

/**
 * Get a list of options for match/mismatch ratio.
 */
function _get_match_mismatch($which) {
	switch ($which) {
		case 'blastn':
      return array(
				 0 => t('1,-2'),
				 1 => t('1,-3'),
				 2 => t('1,-4'),
				 3 => t('2,-3'),
				 4 => t('4,-5'),
				 5 => t('1,-1'),
      );
  }//switch
}


/* 
 **Method Used for tblastn and blastx
 **Method Used in Blast_ui.services.inc
 **  @return  int $gapOpen and $gapExtend
 */


function getGap( $matrix, $gapkey )
 {
   switch ( $matrix )
   {
      case 'PAM30':
         switch ( $gapKey )
         {
            case 0:
               $gapOpen   = 7;
               $gapExtend = 2;
               break;
            case 1:
               $gapOpen   = 6;
               $gapExtend = 2;
               break;
            case 2:
               $gapOpen   = 5;
               $gapExtend = 2;
               break;
            case 3:
               $gapOpen   = 10;
               $gapExtend = 1;
               break;
            case 4:
               $gapOpen   = 9;
               $gapExtend = 1;
               break;
            case 5:
               $gapOpen   = 8;
               $gapExtend = 1;
               break;
         }
         break;
      case 'PAM70':
         switch ( $gapKey )
         {
            case 0:
               $gapOpen   = 8;
               $gapExtend = 2;
               break;
            case 1:
               $gapOpen   = 7;
               $gapExtend = 2;
               break;
            case 2:
               $gapOpen   = 6;
               $gapExtend = 2;
               break;
            case 3:
               $gapOpen   = 11;
               $gapExtend = 1;
               break;
            case 4:
               $gapOpen   = 10;
               $gapExtend = 1;
               break;
            case 5:
               $gapOpen   = 9;
               $gapExtend = 1;
               break;
         }
         break;
      case 'PAM250':
         switch ( $gapKey )
         {
            case 0:
               $gapOpen   = 15;
               $gapExtend = 3;
               break;
            case 1:
               $gapOpen   = 14;
               $gapExtend = 3;
               break;
            case 2:
               $gapOpen   = 13;
               $gapExtend = 3;
               break;
            case 3:
               $gapOpen   = 12;
               $gapExtend = 3;
               break;
            case 4:
               $gapOpen   = 11;
               $gapExtend = 3;
               break;
            case 5:
               $gapOpen   = 17;
               $gapExtend = 2;
               break;
            case 6:
               $gapOpen   = 16;
               $gapExtend = 2;
               break;
            case 7:
               $gapOpen   = 15;
               $gapExtend = 2;
               break;
            case 8:
               $gapOpen   = 14;
               $gapExtend = 2;
               break;
            case 9:
               $gapOpen   = 13;
               $gapExtend = 2;
               break;
            case 10:
               $gapOpen   = 21;
               $gapExtend = 1;
               break;
            case 11:
               $gapOpen   = 20;
               $gapExtend = 1;
               break;
            case 12:
               $gapOpen   = 19;
               $gapExtend = 1;
               break;
            case 13:
               $gapOpen   = 18;
               $gapExtend = 1;
               break;
            case 14:
               $gapOpen   = 17;
               $gapExtend = 1;
               break;
         }
         break;
      case 'BLOSUM80':
         switch ( $gapKey )
         {
            case 0:
               $gapOpen   = 8;
               $gapExtend = 2;
               break;
            case 1:
               $gapOpen   = 7;
               $gapExtend = 2;
               break;
            case 2:
               $gapOpen   = 6;
               $gapExtend = 2;
               break;
            case 3:
               $gapOpen   = 11;
               $gapExtend = 1;
               break;
            case 4:
               $gapOpen   = 10;
               $gapExtend = 1;
               break;
            case 5:
               $gapOpen   = 9;
               $gapExtend = 1;
               break;
         }
         break;
      case 'BLOSUM62':
         switch ( $gapKey )
         {
            case 0:
               $gapOpen   = 11;
               $gapExtend = 2;
               break;
            case 1:
               $gapOpen   = 10;
               $gapExtend = 2;
               break;
            case 2:
               $gapOpen   = 9;
               $gapExtend = 2;
               break;
            case 3:
               $gapOpen   = 8;
               $gapExtend = 2;
               break;
            case 4:
               $gapOpen   = 7;
               $gapExtend = 2;
               break;
            case 5:
               $gapOpen   = 6;
               $gapExtend = 2;
               break;
            case 6:
               $gapOpen   = 13;
               $gapExtend = 1;
               break;
            case 7:
               $gapOpen   = 12;
               $gapExtend = 1;
               break;
            case 8:
               $gapOpen   = 11;
               $gapExtend = 1;
               break;
            case 9:
               $gapOpen   = 10;
               $gapExtend = 1;
               break;
            case 10:
               $gapOpen   = 9;
               $gapExtend = 1;
               break;
         }
         break;
      case 'BLOSUM45':
         switch ( $gapKey )
         {
            case 0:
               $gapOpen   = 13;
               $gapExtend = 3;
               break;
            case 1:
               $gapOpen   = 12;
               $gapExtend = 3;
               break;
            case 2:
               $gapOpen   = 11;
               $gapExtend = 3;
               break;
            case 3:
               $gapOpen   = 10;
               $gapExtend = 3;
               break;
            case 4:
               $gapOpen   = 15;
               $gapExtend = 2;
               break;
            case 5:
               $gapOpen   = 14;
               $gapExtend = 2;
               break;
            case 6:
               $gapOpen   = 13;
               $gapExtend = 2;
               break;
            case 7:
               $gapOpen   = 12;
               $gapExtend = 2;
               break;
            case 8:
               $gapOpen   = 19;
               $gapExtend = 1;
               break;
            case 9:
               $gapOpen   = 18;
               $gapExtend = 1;
               break;
            case 10:
               $gapOpen   = 17;
               $gapExtend = 1;
               break;
            case 11:
               $gapOpen   = 16;
               $gapExtend = 1;
               break;
         }
         break;
      case 'BLOSUM50':
         switch ( $gapKey )
         {
            case 0:
               $gapOpen   = 13;
               $gapExtend = 3;
               break;
            case 1:
               $gapOpen   = 12;
               $gapExtend = 3;
               break;
            case 2:
               $gapOpen   = 11;
               $gapExtend = 3;
               break;
            case 3:
               $gapOpen   = 10;
               $gapExtend = 3;
               break;
            case 4:
               $gapOpen   = 9;
               $gapExtend = 3;
               break;
            case 5:
               $gapOpen   = 16;
               $gapExtend = 2;
               break;
            case 6:
               $gapOpen   = 15;
               $gapExtend = 2;
               break;
            case 7:
               $gapOpen   = 14;
               $gapExtend = 2;
               break;
            case 8:
               $gapOpen   = 13;
               $gapExtend = 2;
               break;
            case 9:
               $gapOpen   = 12;
               $gapExtend = 2;
               break;
            case 10:
               $gapOpen   = 19;
               $gapExtend = 1;
               break;
            case 11:
               $gapOpen   = 18;
               $gapExtend = 1;
               break;
            case 12:
               $gapOpen   = 17;
               $gapExtend = 1;
               break;
            case 13:
               $gapOpen   = 16;
               $gapExtend = 1;
               break;
            case 14:
               $gapOpen   = 15;
               $gapExtend = 1;
               break;
         }
         break;
      case 'BLOSUM90':
         switch ( $gapKey )
         {
            case 0:
               $gapOpen   = 9;
               $gapExtend = 2;
               break;
            case 1:
               $gapOpen   = 8;
               $gapExtend = 2;
               break;
            case 2:
               $gapOpen   = 7;
               $gapExtend = 2;
               break;
            case 3:
               $gapOpen   = 6;
               $gapExtend = 2;
               break;
            case 4:
               $gapOpen   = 11;
               $gapExtend = 1;
               break;
            case 5:
               $gapOpen   = 10;
               $gapExtend = 1;
               break;
            case 6:
               $gapOpen   = 9;
               $gapExtend = 1;
               break;
         }
         break;
         
   }
   return array(
       'gapOpen' => $gapOpen,
      'gapExtend' => $gapExtend 
   );
 }




/* 
/**
 * Get a list of options for gaps.
 */





function _get_gap($which) {
	switch ($which) {
		case 'blastn':
      return array(
				0 => t('Existence: 5 Extension: 2'),
				1 => t('Existence: 2 Extension: 2'),
				2 => t('Existence: 1 Extension: 2'),
				3 => t('Existence: 0 Extension: 2'),
				4 => t('Existence: 3 Extension: 1'),
				5 => t('Existence: 2 Extension: 1'),
				6 => t('Existence: 1 Extension: 1'),
      );
   }//switch
}

/**
 * Translate above gap options into blast gap open and extend costs.
 */
function _set_gap($gap_key) {
 switch ($gap_key) {
   case 0:
      $gapOpen = 5;
      $gapExtend = 2;
      break;
   case 1:
      $gapOpen = 2;
      $gapExtend = 2;
      break;
   case 2:
      $gapOpen = 1;
      $gapExtend = 2;
      break;
   case 3:
      $gapOpen = 0;
      $gapExtend = 2;
      break;
   case 4:
      $gapOpen = 3;
      $gapExtend = 1;
      break;
   case 5:
      $gapOpen = 2;
      $gapExtend = 1;
      break;
   case 6:
      $gapOpen = 1;
      $gapExtend = 1;
      break;
  }//switch
  
  return array('gapOpen' => $gapOpen, 'gapExtend' => $gapExtend);
}

/**
 * Translate mismatch/match ratio option into blast penalty/reward options.
 */
function _set_match_mismatch($m_m) {
  switch ($m_m) {
   case 0:
      $penalty = -2;
      $reward = 1;
      break;
   case 1:
      $penalty = -3;
      $reward = 1;
      break;
   case 2:
      $penalty = -4;
      $reward = 1;
      break;
   case 3:
      $penalty = -3;
      $reward = 2;
      break;
   case 4:
      $penalty = -5;
      $reward = 4;
      break;
   case 5:
      $penalty = -1;
      $reward = 1;
      break;
  }//switch
  
  return array('penalty' => $penalty, 'reward' => $reward);
}
