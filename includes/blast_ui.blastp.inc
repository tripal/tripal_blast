<?php

function blast_protein_form($form, &$form_state) {
  

//Protein BLAST

  $form['pBLAST'] = array(
    '#type' => 'fieldset',
    '#title' => t('Standard Nucleotide BLAST'),
    '#collapsible' => TRUE, 
    '#collapsed' => FALSE,  
  );
$form['pBLAST']['FASTA'] = array(
    '#type' => 'textarea',
    '#title' => t('Enter FASTA sequence(s)'),
    '#description'=>t('Enter query sequence(s) in the text area.'),
  );


// Upload a file as an alternative to enter a query sequence 
  $form['#attributes']['enctype'] = 'multipart/form-data';
  $form['pBLAST']['UPLOAD'] = array(
  	'#prefix' => 'Or upload your query files:	',
  	'#type' => 'file',
  	'#description' => t('Please give a text file, not a MS-Word or other document, you can upload up to 10 Mb.'),
  );

//DATABASE//

	$form['DB'] = array(
    '#type' => 'fieldset',
    '#title' => t('BLAST Assembled KnowPulse Genomes'),
    '#collapsible' => TRUE, 
    '#collapsed' => FALSE,  
  );
  $form['DB']['SELECT_DB'] = array(
       '#type' => 'select',
       '#title' => t('KnowPulse Databases:'),
       '#options' => array(
          0 => t('Human immunodeficiency virus 1'),
          1 => t('Human immunodeficiency virus 2'),
          2 => t('Simian immunodeficiency virus'),
          3 => t('Human herpesvirus 2'),
          4 => t('Human herpesvirus 1'),
       ),
       '#default_value' => NULL,
   );
  
// Upload a file as an alternative to enter a query sequence 
  $form['#attributes']['enctype'] = 'multipart/form-data';
  $form['DB']['DBUPLOAD'] = array(
  	'#prefix' => 'Or upload your own dataset:	',
  	'#type' => 'file',
  	'#description' => t('Please give a text file, not a MS-Word or other document, you can upload up to 10 Mb.'),
  );
  
//Algorithm parameters

	$form['ALG'] = array(
    '#type' => 'fieldset',
    '#title' => t('Algorithm parameters'),
    '#collapsible' => TRUE, 
    '#collapsed' => TRUE,  
  );

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
  	'#title' => t('Expect threshold'), 
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
  
//Submit 

  $form['next'] = array(
    '#type' => 'submit',
    '#value' => ' BLAST ',
  );
  return $form;
}

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

function ajax_example_dependent_dropdown_callback($form, $form_state) {
  return $form['ALG']['SParam']['gapCost'];
}



