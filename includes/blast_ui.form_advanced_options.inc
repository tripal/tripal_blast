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
    '#description' => t('Select the maximum number of unique target sequences per query sequence to show results for.'),
  );

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
    '#ajax' => array(
      'callback' => 'gap_cost_callback',
      'wrapper' => 'gap_cost_wrapper',
    ),
  );

  $m_m_set = $defaults['matchmiss'];
  if (isset($form_state['values']) && isset($form_state['values']['M&MScores'])) {
    $m_m_set = $form_state['values']['M&MScores'];
  }

  $form['ALG']['SParam']['gapCost'] = array(
    '#type' => 'select',
    '#title' => t('Gap Costs:'),
    '#options' => _get_gap_options('blastn', $m_m_set),
    '#default_value' => $defaults['gap'],
    '#description' => t('Cost to create and extend a gap in an alignment.'),
    '#prefix' => '<div id="gap_cost_wrapper">',
    '#suffix' => '</div>',
  );
}

/**
 * Validate the advanced options provided by the BLASTn form above.
 *
 * @see blast_ui_blastn_advanced_options_form().
 */
function blast_ui_blastn_advanced_options_form_validate($form, $form_state) {

  // Ensure that our textfields are what we expect them to be since we will
  // use them to generate the BLAST command.
  // First up, e-value. We expect the evalue to be a very small yet still
  // positive number. It may be given in scientific notation which is
  // luckily supported by is_numeric().
  if (!is_numeric($form_state['values']['eVal'])) {
    form_set_error('eVal', 'The e-value should be a very small number (scientific notation is supported). For example, <em>0.001</em> or, even better, <em>1e-10</em>.');
  }
}

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

  return array(
    'max_target_seqs' => $numAlign,
    'evalue' => $eVal,
    'word_size' => $wordSize,
    'gapopen' => $gap['gapOpen'],
    'gapextend' => $gap['gapExtend'],
    'penalty' => $m_m['penalty'],
    'reward' => $m_m['reward'],
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
    $defaults = _get_default_values($form_state['prev_blast']->options, 'blastx');
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
      'callback' => 'matrix_gap_cost_callback',
      'wrapper' => 'gap_cost_wrapper',
    ),
  );
    
  $matrix_set = $defaults['matrix'];
  if (isset($form_state['values']) && isset($form_state['values']['Matrix'])) {
    $matrix_set = $form_state['values']['Matrix'];
  }

  $form['ALG']['SParam']['gapCost'] = array(
    '#type' => 'select',
    '#title' => t('Gap Costs:'),
    '#prefix' => '<div id="gap_cost_wrapper">',
    '#suffix' => '</div>',
    '#options' => _get_gap_for_matrix($matrix_set),
    '#default_value' => $defaults['gap'],
    '#description' => t('Cost to create and extend a gap in an alignment.'),
  );
}

/**
 * Validate the advanced options provided by the BLASTn form above.
 *
 * @see blast_ui_blastx_advanced_options_form().
 */
function blast_ui_blastx_advanced_options_form_validate($form, $form_state) {

  // Ensure that our textfields are what we expect them to be since we will
  // use them to generate the BLAST command.
  // First up, e-value. We expect the evalue to be a very small yet still
  // positive number. It may be given in scientific notation which is
  // luckily supported by is_numeric().
  if (!is_numeric($form_state['values']['eVal'])) {
    form_set_error('eVal', 'The e-value should be a very small number (scientific notation is supported). For example, <em>0.001</em> or, even better, <em>1e-10</em>.');
  }
}

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
    $defaults = _get_default_values($form_state['prev_blast']->options, 'blastp');
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
      'callback' => 'matrix_gap_cost_callback',
      'wrapper' => 'gap_cost_wrapper',
    ),
  );
    
  $matrix_set = $defaults['matrix'];
  if (isset($form_state['values']) && isset($form_state['values']['Matrix'])) {
    $matrix_set = $form_state['values']['Matrix'];
  }

  $form['ALG']['SParam']['gapCost'] = array(
    '#type' => 'select',
    '#title' => t('Gap Costs:'),
    '#prefix' => '<div id="gap_cost_wrapper">',
    '#suffix' => '</div>',
    '#options' => _get_gap_for_matrix($matrix_set),
    '#default_value' => $defaults['gap'],
    '#description' => t('Cost to create and extend a gap in an alignment.'),
  );
}

/**
 * Validate the advanced options provided by the BLASTp form above.
 *
 * @see blast_ui_blastp_advanced_options_form().
 */
function blast_ui_blastp_advanced_options_form_validate($form, $form_state) {

  // Ensure that our textfields are what we expect them to be since we will
  // use them to generate the BLAST command.
  // First up, e-value. We expect the evalue to be a very small yet still
  // positive number. It may be given in scientific notation which is
  // luckily supported by is_numeric().
  if (!is_numeric($form_state['values']['eVal'])) {
    form_set_error('eVal', 'The e-value should be a very small number (scientific notation is supported). For example, <em>0.001</em> or, even better, <em>1e-10</em>.');
  }
}

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

  //$qRange = $form_state['values']['qRange'];

  // Expand Gap Cost key into open and extend penalties
  $matrix = $form_state['values']['Matrix'];
  $gapKey = $form_state['values']['gapCost'];


  $gap = _set_gap($gapKey);
  $gapOpen = $gap['gapOpen'];
  $gapExtend = $gap['gapExtend'];

  return array(
    'max_target_seqs' => $numAlign,
    'evalue' => $eVal,
    'word_size' => $wordSize,
    'gapopen' => $gapOpen,
    'gapextend' => $gapExtend,
    //'culling_limit' => $qRange,
    'matrix' => $matrix,
  );
}//blast_ui_blastp_advanced_options_form_submit

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
    $defaults = _get_default_values($form_state['prev_blast']->options, 'tblastn');
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
    '#description' => t('Assigns a score for aligning pairs of residues, and determines overall alignment score.'),
    '#ajax' => array(
      'callback' => 'matrix_gap_cost_callback',
      'wrapper' => 'gap_cost_wrapper',
    ),
  );

  $matrix_set = $defaults['matrix'];
  if (isset($form_state['values']) && isset($form_state['values']['Matrix'])) {
    $matrix_set = $form_state['values']['Matrix'];
  }

  $form['ALG']['SParam']['gapCost'] = array(
    '#type' => 'select',
    '#title' => t('Gap Costs:'),
    '#prefix' => '<div id="gap_cost_wrapper">',
    '#suffix' => '</div>',
    '#options' => _get_gap_for_matrix($matrix_set),
    '#default_value' => $defaults['gap'],
    '#description' => t('Cost to create and extend a gap in an alignment.'),
  );
}

/**
 * Validate the advanced options provided by the tBLASTn form above.
 *
 * @see blast_ui_tblastn_advanced_options_form().
 */
function blast_ui_tblastn_advanced_options_form_validate($form, $form_state) {

  // Ensure that our textfields are what we expect them to be since we will
  // use them to generate the BLAST command.
  // First up, e-value. We expect the evalue to be a very small yet still
  // positive number. It may be given in scientific notation which is
  // luckily supported by is_numeric().
  if (!is_numeric($form_state['values']['eVal'])) {
    form_set_error('eVal', 'The e-value should be a very small number (scientific notation is supported). For example, <em>0.001</em> or, even better, <em>1e-10</em>.');
  }
}

/**
 * Processed the advanced options provided by the tBLASTn form above.
 *
 * @see blast_ui_tblastn_advanced_options_form().
 */
function blast_ui_tblastn_advanced_options_form_submit($form, $form_state) {

  return blast_ui_blastp_advanced_options_form_submit($form, $form_state);
}


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
 * Fill the gap penalty dropdown list with appropriate options given selected
 * matrix
 *
 * @return
 * An array containing open and extension gap values for the chosen matrix (to
 *   fill the second dropdown list)
 */
function _get_gap_for_matrix($key = '') {
  switch ($key) {
    case 'PAM30':
      return _make_gaps(array('7_2', '6_2', '5_2', '10_1', '8_1', '13_3', '15_3', '14_1', 
                              '14_2')
      );
    case 'PAM70':
      return _make_gaps(array('8_2', '7_2', '6_2', '11_1', '10_1', '9_1', '12_3', '11_2'));
    case 'PAM250':
      return _make_gaps(array('15_3', '14_3', '13_3', '12_3', '11_3', '17_2', '16_2', 
                              '15_2', '14_2', '13_2', '21_1', '20_1', '19_1', '18_1', 
                              '17_1')
      );
    case 'BLOSUM80':
      return _make_gaps(array('8_2', '7_2', '6_2', '11_1', '10_1', '9_1'));
    case 'BLOSUM62':
      return _make_gaps(array('11_2', '10_2', '9_2', '8_2', '7_2', '6_2', '13_1',
                              '12_1', '11_1', '10_1', '9_1')
      );
    case 'BLOSUM45':
      return _make_gaps(array('13_3', '12_3', '11_3', '10_3', '15_2', '14_2', '13_2', 
                              '12_2', '19_1', '18_1', '17_1', '16_1')
      );
    case 'BLOSUM50':
      return _make_gaps(array('13_3', '12_3', '11_3', '10_3', '9_3', '16_2', '15_2', 
                              '14_2', '13_2', '12_2', '19_1', '18_1', '17_1', '16_1', 
                              '15_1')
      );
    case 'BLOSUM90':
      return _make_gaps(array('9_2', '8_2', '7_2', '6_2', '11_1', '10_1', '9_1'));
  }
}//_get_gap_for_matrix

/**
 * Respond to Ajax dropdown call
 */
function matrix_gap_cost_callback($form, &$form_state) {

  return $form['B']['ALG']['SParam']['gapCost'];
}


/**
 * Get default form values; may come from saved job data if user is re-running
 *   a previous job.
 */
function _get_default_values($options, $program) {
  // restore previous values or set to default
  $max_target = (isset($options['max_target_seqs']))
    ? $options['max_target_seqs'] : 500;
  $short_queries = (isset($options['shortQueries']))
    ? $options['shortQueries'] : TRUE;
  $evalue = (isset($options['evalue']))
    ? $options['evalue'] : variable_get('eVal', 0.001);
  $word_size = (isset($options['word_size']))
    ? $options['word_size'] : 11;

  // match/mismatch
  $matchmiss = 0;
  $reward = (isset($options['reward']))
    ? $options['reward'] : 1;
  $penalty = (isset($options['penalty']))
    ? $options['penalty'] : -2;
  if ($reward == 1) {
    switch ($penalty) {
      case -1:
        $matchmiss = 5;
        break;
      case -2:
        $matchmiss = 0;
        break;
      case -3:
        $matchmiss = 1;
        break;
      case -4:
        $matchmiss = 2;
        break;
    }
  }
  else {
    if ($reward == 2) {
      $matchmiss = 3;
    }
    else {
      if ($reward == 3) {
        $matchmiss = 4;
      }
      else {
        if ($reward == 4) {
          $matchmiss = 5;
        }
      }
    }
  }

  // gap
  if (isset($options['gapopen']) && isset($options['gapextend'])) {
    $gapopen = $options['gapopen'];
    $gapextend = $options['gapextend'];
  }
  else {
    switch ($program) {
      case 'blastn':
        $gapopen = 5;
        $gapextend = 2;
        break;
      case 'blastp':
      case 'blastx':
      case 'tblastn':
        $gapopen = 11;
        $gapextend = 1;
        break;
    }
  }
  $gap = $gapopen.'_'.$gapextend;
  
  // matrix
  $matrix = (isset($options['matrix']))
    ? $options['matrix'] : 'BLOSUM62';

  // all done
  return array(
    'max_target_seqs' => $max_target,
    'short_queries' => $short_queries,
    'word_size' => $word_size,
    'evalue' => $evalue,
    'matchmiss' => $matchmiss,
    'gap' => $gap,
    'matrix' => $matrix,
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
        0 => t(' '),
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

/**
 * @param $which - the blast program being run
 * @param $m_m - the match mismatch scores.
 *
 * @return array
 */
function _get_gap_options($which, $m_m) {
  switch ($which) {
    case 'blastn':
      switch ($m_m) {
        case 0: //1, -2
          return _make_gaps(array('5_2', '2_2', '1_2', '0_2', '3_1', '2_1', '1_1'));
        case 1: //1, -3
          return _make_gaps(array('5_2', '2_2', '1_2', '1_2', '0_2', '2_1', '1_1'));
        case 2: // 1, -4
          return _make_gaps(array('5_2', '1_2', '0_2', '2_1', '1_1'));
        case 3: //2, -3
          return _make_gaps(array('4_4', '2_4', '0_4', '3_3', '6_2', '5_2', '4_2', '2_2'));
        case 4: //4, -5
          return _make_gaps(array('12_8', '6_5', '5_5', '4_5', '3_5'));
        case 5: //1, -1
          return _make_gaps(array('5_2', '3_2', '2_2', '1_2', '0_2', '4_1', '3_1', '2_1'));
      }
  }
}

/**
 * @param $gap_array - array of gap abbreviations
 *
 * @return array
 */
function _make_gaps($gap_array) {
  $a = array();
  
  foreach ($gap_array as $g) {
    $parts = explode('_', $g);
    $a[$g] = t("Existence: $parts[0] Extension: $parts[1]");
  }
  
  return $a;
}

/**
 * Translate gap abbreviation into blast gap open and extend costs.
 * @param $gap_key - a gap open/extend abbreviation
 */
function _set_gap($gap_key) {
  $parts = explode('_', $gap_key);

  return array('gapOpen' => $parts[0], 'gapExtend' => $parts[1]);
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

/**
 * AJAX callback for match and gap cost.
 * @param $form
 * @param $form_state
 *
 * @return mixed
 */
function gap_cost_callback($form, &$form_state) {

  return $form['B']['ALG']['SParam']['gapCost'];
}


