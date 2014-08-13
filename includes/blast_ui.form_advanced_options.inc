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
    '#options' => array(
      0 => t('10'),
      1 => t('50'),
      2 => t('100'),
      3 => t('250'),
      4 => t('500'),
      5 => t('1000'),
      6 => t('5000'),
      7 => t('10000'),
      8 => t('20000'),
    ),
    '#default_value' => 2,
    '#description' => t('Select the maximum number of aligned sequences to display'),
  );

  $form['ALG']['GParam']['shortQueries'] = array(
    '#type' => 'checkbox',
    '#title' => t('Automatically adjust parameters for short input sequences'),
    '#default_value' => TRUE,
  );

  $form['ALG']['GParam']['eVal'] = array(
    '#type' => 'textfield',
    '#title' => t('e-Value (Expected Threshold)'),
    '#default_value' => 10,
    '#size' => 12,
    '#maxlength' => 20,
    '#description' => t('Expected number of chance matches in a random model. This number should be give in a decimal format. <a href="http://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml#expect" target="_blank">More Information</a> | <a href="https://www.youtube.com/watch?v=nO0wJgZRZJs" target="_blank">Expect value vedio tutorial</a>'),
  );

  $form['ALG']['GParam']['wordSize'] = array(
    '#type' => 'select',
    '#title' => t('Word size:'),
    '#options' => array(
      0 => t('16'),
      1 => t('20'),
      2 => t('24'),
      3 => t('28'),
      4 => t('32'),
      5 => t('48'),
      6 => t('64'),
      7 => t('128'),
      8 => t('256'),
    ),
    '#default_value' => 3,
    '#description' => t('The length of the seed that initiates an alignment'),
  );

  $form['ALG']['GParam']['qRange'] = array(
    '#type' => 'textfield',
    '#title' => t('Max matches in a query range'),
    '#default_value' => 0,
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
    '#options' => array(
       0 => t('1,-2'),
       1 => t('1,-3'),
       2 => t('1,-4'),
       3 => t('2,-3'),
       4 => t('4,-5'),
       5 => t('1,-1'),
     ),
    '#default_value' => 0,
    '#description' => t('Reward and penalty for matching and mismatching bases.'),
   );

   $form['ALG']['SParam']['gapCost'] = array(
    '#type' => 'select',
    '#title' => t('Gap Costs:'),
    '#options' => array(
      0 => t('Existence: 5 Extension: 2'),
      1 => t('Existence: 2 Extension: 2'),
      2 => t('Existence: 1 Extension: 2'),
      3 => t('Existence: 0 Extension: 2'),
      4 => t('Existence: 3 Extension: 1'),
      5 => t('Existence: 2 Extension: 1'),
      6 => t('Existence: 1 Extension: 1'),
    ),
    '#default_value' => 0,
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
  $gapKey = $form_state['values']['gapCost'];
  switch ($gapKey) {
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
  }

  // Epand Match/Mismatch option into penalty/reward values
  // @todo Amir: Is the switch supposed to be for $scoreKey?
  $scoreKey = $form_state['values']['M&MScores'];
  switch ($gapKey) {
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
  }

  return array(
    'evalue' => $eVal,
    'word_size' => $wordSize,
    'gapopen' => $gapOpen,
    'gapextend' => $gapExtend,
    'penalty' =>  $penalty,
    'reward' => $reward
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

  $form['ALG']['GParam'] = array(
   '#type' => 'fieldset',
   '#title' => t('General parameters'),
   '#collapsible' => FALSE,
  );

  $form['ALG']['GParam']['maxTarget'] = array(
    '#type' => 'select',
    '#title' => t('Max target sequences:'),
    '#options' => array(
      0 => t('10'),
      1 => t('50'),
      2 => t('100'),
      3 => t('250'),
      4 => t('500'),
      5 => t('1000'),
      6 => t('5000'),
      7 => t('10000'),
      8 => t('20000'),
    ),
    '#default_value' => 2,
    '#description' => t('Select the maximum number of aligned sequences to display'),
  );

  $form['ALG']['GParam']['eVal'] = array(
    '#type' => 'textfield',
    '#title' => t('e-Value (Expected Threshold)'),
    '#default_value' => 10,
    '#size' => 12,
    '#maxlength' => 20,
    '#description' => t('Expected number of chance matches in a random model. This number should be give in a decimal format. <a href="http://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml#expect" target="_blank">More Information</a> | <a href="https://www.youtube.com/watch?v=nO0wJgZRZJs" target="_blank">Expect value vedio tutorial</a>'),
  );

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

  $eVal = $form_state['values']['eVal'];

  $trgtKey = $form_state['values']['maxTarget'];
  $numAlign = $form['ALG']['GParam']['maxTarget']['#options'][$trgtKey];

  return array(
    'evalue' => $eVal,
  );

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

  //General parameters

  $form['ALG']['GParam'] = array(
    '#type' => 'fieldset',
    '#title' => t('General parameters'),
    '#collapsible' => FALSE,
  );

  $form['ALG']['GParam']['maxTarget'] = array(
    '#type' => 'select',
    '#title' => t('Max target sequences:'),
    '#options' => array(
       0 => t('10'),
       1 => t('50'),
       2 => t('100'),
       3 => t('250'),
       4 => t('500'),
       5 => t('1000'),
       6 => t('5000'),
       7 => t('10000'),
       8 => t('20000'),
    ),
    '#default_value' => 2,
    '#description' => t('Select the maximum number of aligned sequences to display'),
  );

  $form['ALG']['GParam']['shortQueries'] = array(
   '#type' => 'checkbox',
   '#title' => t('Automatically adjust parameters for short input sequences'),
   '#default_value' => TRUE,
  );

  $form['ALG']['GParam']['eVal'] = array(
  	'#type' => 'textfield',
  	'#title' => t('e-value(Expect threshold)'),
  	'#default_value' => 10,
  	'#size' => 12,
  	'#maxlength' => 20,
  	'#description' => t('Expected number of chance matches in a random model.'),
  );

  $form['ALG']['GParam']['wordSize'] = array(
    '#type' => 'select',
    '#title' => t('Word size:'),
    '#options' => array(
       0 => t('2'),
       1 => t('3'),
    ),
    '#default_value' => 1,
    '#description' => t('The length of the seed that initiates an alignment'),
  );

  $form['ALG']['GParam']['qRange'] = array(
   '#type' => 'textfield',
   '#title' => t('Max matches in a query range'),
   '#default_value' => 0,
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

  $options_first = _ajax_example_get_first_dropdown_options();
  $selected = isset($form_state['values']['MATRIX'] ) ? $form_state['values']['MATRIX'] : key($options_first);

  $form['ALG']['SParam']['MATRIX'] = array(
    '#type' => 'select',
    '#title' => 'Matrix',
    '#options' => $options_first,
    '#default_value' => $selected,
    '#description' => t('Assigns a score for aligning pairs of residues, and determines overall alignment score..'),
    '#ajax' => array(
      'callback' => 'ajax_example_dependent_dropdown_callback',
      'wrapper' => 'dropdown-second-replace',
    ),
  );

  $form['ALG']['SParam']['gapCost'] = array(
    '#type' => 'select',
    '#title' => t('Gap Costs:'),
    '#prefix' => '<div id="dropdown-second-replace">',
    '#suffix' => '</div>',
    '#options' => _ajax_example_get_second_dropdown_options($selected),
    '#default_value' => 2,
    '#description' => t('Cost to create and extend a gap in an alignment.'),
  );

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

}

/**
 * Validate the advanced options provided by the BLASTn form above.
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

  // Expand Gap Cost key into open and extend penalties
  $gapKey = $form_state['values']['MATRIX'];
  switch ($gapKey) {
   case 0:
     $matrix ="PAM30";
     $gapKey = $form_state['values']['gapCost'];
     switch ($gapKey) {
      case 0:
         $gapOpen = 7;
         $gapExtend = 2;
         break;
      case 1:
         $gapOpen = 6;
         $gapExtend = 2;
         break;
      case 2:
         $gapOpen = 5;
         $gapExtend = 2;
         break;
      case 3:
         $gapOpen = 10;
         $gapExtend = 1;
         break;
      case 4:
         $gapOpen = 9;
         $gapExtend = 1;
         break;
      case 5:
         $gapOpen = 8;
         $gapExtend = 1;
         break;
     }
     break;
   case 1:
     $matrix ="PAM70";
     $gapKey = $form_state['values']['gapCost'];
     switch ($gapKey) {
      case 0:
         $gapOpen = 8;
         $gapExtend = 2;
         break;
      case 1:
         $gapOpen = 7;
         $gapExtend = 2;
         break;
      case 2:
         $gapOpen = 6;
         $gapExtend = 2;
         break;
      case 3:
         $gapOpen = 11;
         $gapExtend = 1;
         break;
      case 4:
         $gapOpen = 10;
         $gapExtend = 1;
         break;
      case 5:
         $gapOpen = 9;
         $gapExtend = 1;
         break;
     }
     break;
   case 2:
     $matrix ="PAM250";
     $gapKey = $form_state['values']['gapCost'];
     switch ($gapKey) {
      case 0:
         $gapOpen = 15;
         $gapExtend = 3;
         break;
      case 1:
         $gapOpen = 14;
         $gapExtend = 3;
         break;
      case 2:
         $gapOpen = 13;
         $gapExtend = 3;
         break;
      case 3:
         $gapOpen = 12;
         $gapExtend = 3;
         break;
      case 4:
         $gapOpen = 11;
         $gapExtend = 3;
         break;
      case 5:
         $gapOpen = 17;
         $gapExtend = 2;
         break;
      case 6:
         $gapOpen = 16;
         $gapExtend = 2;
         break;
      case 7:
         $gapOpen = 15;
         $gapExtend = 2;
         break;
      case 8:
         $gapOpen = 14;
         $gapExtend = 2;
         break;
      case 9:
         $gapOpen = 13;
         $gapExtend = 2;
         break;
      case 10:
         $gapOpen = 21;
         $gapExtend = 1;
         break;
      case 11:
         $gapOpen = 20;
         $gapExtend = 1;
         break;
      case 12:
         $gapOpen = 19;
         $gapExtend = 1;
         break;
      case 13:
         $gapOpen = 18;
         $gapExtend = 1;
         break;
      case 14:
         $gapOpen = 17;
         $gapExtend = 1;
         break;
     }
     break;
   case 3:
     $matrix ="BLOSUM80";
     $gapKey = $form_state['values']['gapCost'];
     switch ($gapKey) {
      case 0:
         $gapOpen = 8;
         $gapExtend = 2;
         break;
      case 1:
         $gapOpen = 7;
         $gapExtend = 2;
         break;
      case 2:
         $gapOpen = 6;
         $gapExtend = 2;
         break;
      case 3:
         $gapOpen = 11;
         $gapExtend = 1;
         break;
      case 4:
         $gapOpen = 10;
         $gapExtend = 1;
         break;
      case 5:
         $gapOpen = 9;
         $gapExtend = 1;
         break;
     }
      break;
   case 4:
     $matrix ="BLOSUM62";
     $gapKey = $form_state['values']['gapCost'];
     switch ($gapKey) {
      case 0:
         $gapOpen = 11;
         $gapExtend = 2;
         break;
      case 1:
         $gapOpen = 10;
         $gapExtend = 2;
         break;
      case 2:
         $gapOpen = 9;
         $gapExtend = 2;
         break;
      case 3:
         $gapOpen = 8;
         $gapExtend = 2;
         break;
      case 4:
         $gapOpen = 7;
         $gapExtend = 2;
         break;
      case 5:
         $gapOpen = 6;
         $gapExtend = 2;
         break;
      case 6:
         $gapOpen = 13;
         $gapExtend = 1;
         break;
      case 7:
         $gapOpen = 12;
         $gapExtend = 1;
         break;
      case 8:
         $gapOpen = 11;
         $gapExtend = 1;
         break;
      case 9:
         $gapOpen = 10;
         $gapExtend = 1;
         break;
      case 10:
         $gapOpen = 9;
         $gapExtend = 1;
         break;
     }
      break;
   case 5:
     $matrix ="BLOSUM45";
     $gapKey = $form_state['values']['gapCost'];
     switch ($gapKey) {
      case 0:
         $gapOpen = 13;
         $gapExtend = 3;
         break;
      case 1:
         $gapOpen = 12;
         $gapExtend = 3;
         break;
      case 2:
         $gapOpen = 11;
         $gapExtend = 3;
         break;
      case 3:
         $gapOpen = 10;
         $gapExtend = 3;
         break;
      case 4:
         $gapOpen = 15;
         $gapExtend = 2;
         break;
      case 5:
         $gapOpen = 14;
         $gapExtend = 2;
         break;
      case 6:
         $gapOpen = 13;
         $gapExtend = 2;
         break;
      case 7:
         $gapOpen = 12;
         $gapExtend = 2;
         break;
      case 8:
         $gapOpen = 19;
         $gapExtend = 1;
         break;
      case 9:
         $gapOpen = 18;
         $gapExtend = 1;
         break;
      case 10:
         $gapOpen = 17;
         $gapExtend = 1;
         break;
      case 11:
         $gapOpen = 16;
         $gapExtend = 1;
         break;
     }
     break;
   case 6:
     $matrix ="BLOSUM50";
     $gapKey = $form_state['values']['gapCost'];
     switch ($gapKey) {
      case 0:
         $gapOpen = 13;
         $gapExtend = 3;
         break;
      case 1:
         $gapOpen = 12;
         $gapExtend = 3;
         break;
      case 2:
         $gapOpen = 11;
         $gapExtend = 3;
         break;
      case 3:
         $gapOpen = 10;
         $gapExtend = 3;
         break;
      case 4:
         $gapOpen = 9;
         $gapExtend = 3;
         break;
      case 5:
         $gapOpen = 16;
         $gapExtend = 2;
         break;
      case 6:
         $gapOpen = 15;
         $gapExtend = 2;
         break;
      case 7:
         $gapOpen = 14;
         $gapExtend = 2;
         break;
      case 8:
         $gapOpen = 13;
         $gapExtend = 2;
         break;
      case 9:
         $gapOpen = 12;
         $gapExtend = 2;
         break;
      case 10:
         $gapOpen = 19;
         $gapExtend = 1;
         break;
      case 11:
         $gapOpen = 18;
         $gapExtend = 1;
         break;
      case 12:
         $gapOpen = 17;
         $gapExtend = 1;
         break;
      case 13:
         $gapOpen = 16;
         $gapExtend = 1;
         break;
      case 14:
         $gapOpen = 15;
         $gapExtend = 1;
         break;
     }
     break;
   case 7:
     $matrix ="BLOSUM90";
     $gapKey = $form_state['values']['gapCost'];
     switch ($gapKey) {
      case 0:
         $gapOpen = 9;
         $gapExtend = 2;
         break;
      case 1:
         $gapOpen = 8;
         $gapExtend = 2;
         break;
      case 2:
         $gapOpen = 7;
         $gapExtend = 2;
         break;
      case 3:
         $gapOpen = 6;
         $gapExtend = 2;
         break;
      case 4:
         $gapOpen = 11;
         $gapExtend = 1;
         break;
      case 5:
         $gapOpen = 10;
         $gapExtend = 1;
         break;
      case 6:
         $gapOpen = 9;
         $gapExtend = 1;
         break;
     }
     break;
  }

  return array(
    'evalue' => $eVal,
    'word_size' => $wordSize,
    'gapopen' => $gapOpen,
    'gapextend' => $gapExtend,
    'matrix' => $matrix
  );
}

/**
 * Fill the first dropdown list with appropriate options
 *
 * @return
 * An array consisting of matrices name for the first dropdown list
 */
function _ajax_example_get_first_dropdown_options() {
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
 * Fill the second dropdown list with appropriate options
 *
 * @return
 * An array containing open and extension gap values for the chosen matrix (to fill the second dropdown list)
 */
function _ajax_example_get_second_dropdown_options($key = '') {
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
    } else {
    	return array();
    }
}

/**
 * Respond to Ajax dropdown call
 */
function ajax_example_dependent_dropdown_callback($form, $form_state) {
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

  $form['ALG']['GParam'] = array(
   '#type' => 'fieldset',
   '#title' => t('General parameters'),
   '#collapsible' => FALSE,
  );

  $form['ALG']['GParam']['maxTarget'] = array(
    '#type' => 'select',
    '#title' => t('Max target sequences:'),
    '#options' => array(
      0 => t('10'),
      1 => t('50'),
      2 => t('100'),
      3 => t('250'),
      4 => t('500'),
      5 => t('1000'),
      6 => t('5000'),
      7 => t('10000'),
      8 => t('20000'),
    ),
    '#default_value' => 2,
    '#description' => t('Select the maximum number of aligned sequences to display'),
  );

  $form['ALG']['GParam']['eVal'] = array(
    '#type' => 'textfield',
    '#title' => t('e-Value (Expected Threshold)'),
    '#default_value' => 10,
    '#size' => 12,
    '#maxlength' => 20,
    '#description' => t('Expected number of chance matches in a random model. This number should be give in a decimal format. <a href="http://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml#expect" target="_blank">More Information</a> | <a href="https://www.youtube.com/watch?v=nO0wJgZRZJs" target="_blank">Expect value vedio tutorial</a>'),
  );

}

/**
 * Validate the advanced options provided by the BLASTn form above.
 *
 * @see blast_ui_tblastn_advanced_options_form().
 */
function blast_ui_tblastn_advanced_options_form_validate($form, $form_state) { }

/**
 * Processed the advanced options provided by the yBLASTn form above.
 *
 * @see blast_ui_tblastn_advanced_options_form().
 */
function blast_ui_tblastn_advanced_options_form_submit($form, $form_state) {

  $eVal = $form_state['values']['eVal'];

  $trgtKey = $form_state['values']['maxTarget'];
  $numAlign = $form['ALG']['GParam']['maxTarget']['#options'][$trgtKey];

  return array(
    'evalue' => $eVal,
  );

}
