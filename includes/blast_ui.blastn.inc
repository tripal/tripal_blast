<?php

/**
 * @file
 * Contains all functions for the nucleotide blast module 
 */


/**
 * Form constructor for the nucleotide blast form.
 *
 * @see blast_nucleotide_form_validate()
 * @see blast_nucleotide_form_submit()
 *
 */
function blast_nucleotide_form($form, &$form_state) {
  
//Nucleotide BLAST

$form['nBLAST'] = array(
    '#type' => 'fieldset',
    '#title' => t('Enter Query Sequence'),
    '#description' => t('Enter one or more queries in the top text box or use the browse button to upload a file from your local disk. The file may contain a single sequence or a list of sequences. In both cases, the data must be in FASTA format. <a href="http://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml" target="_blank">More information..</a> '),
    '#collapsible' => TRUE, 
    '#collapsed' => FALSE,  
  );
$form['nBLAST']['FASTA'] = array(
    '#type' => 'textarea',
    '#title' => t('Enter FASTA sequence(s)'),
    '#description'=>t('Enter query sequence(s) in the text area.'),
  );

// Upload a file as an alternative to enter a query sequence 
$form['#attributes']['enctype'] = 'multipart/form-data';
$form['nBLAST']['UPLOAD'] = array(
  	'#prefix' => 'Or upload your query files:	',
  	'#type' => 'file',
  	'#description' => t('The file should be a plain-text FASTA file and not a .doc, .docx, etc. It cannot be greater than 10 Mb in size.'),
  );


//DATABASE//

$form['DB'] = array(
  '#type' => 'fieldset',
  '#title' => t('Choose Search Set'),
  '#description' => t('Choose from one of the nucleotide BLAST databases listed below. You can also use the browse button to upload a file from your local disk. The file may contain a single sequence or a list of sequences. '),
  '#collapsible' => TRUE, 
  '#collapsed' => FALSE,  
);
  
$options = _DB_options();
$form['DB']['SELECT_DB'] = array(
 '#type' => 'select',
 '#title' => t('Nucleotide BLAST Databases:'),
 '#options' => $options,
 '#default_value' => t('Select a database'),
);
  
// Upload a file as an alternative to enter a query sequence 
$form['#attributes']['enctype'] = 'multipart/form-data';
$form['DB']['DBUPLOAD'] = array(
   '#prefix' => 'Or upload your own dataset:	',
  	'#type' => 'file',
  	'#description' => t('The file should be a plain-text FASTA file and not a .doc, .docx, etc. It cannot be greater than 10 Mb in size.'),
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
  
//Submit 

  $form['submit'] = array(
    '#type' => 'submit',
    '#default_value' => ' BLAST ',
  );
  
  return $form;
}

/**
 * Form validation handler for blast_nucleotide_form().
 *
 * @see blast_nucleotide_form_validate()
 */
function blast_nucleotide_form_validate($form, &$form_state) {
   $fastaSeq = $form_state['input']['FASTA'];
   if (isset($fastaSeq)) {
      if(_validateFasta($fastaSeq)){
          form_set_error('nBLAST', t('Error: Failed to read the Blast query: Wrong format provided for FASTA nucleotide sequence'));
      } else {
	//$seq_filename = "/tmp/user__query_file.fasta";
	//file_put_contents ( $seq_filename , $fastaSeq);
        $form_state['qFlag'] = 'seqQuery';
      }  
   }
   $upQuery = file_save_upload('UPLOAD', array('file_validate_extensions' => array('txt fasta fa fna')), FILE_EXISTS_RENAME);
   if ($upQuery) {
      $upQuery_uri = $upQuery->uri;
      $form_state['upQuery_path'] = drupal_realpath($upQuery_uri);
      $upQuery_content = file_get_contents($form_state['upQuery_path']);
      if(_validateFasta($upQuery_content)){
          form_set_error('nBLAST', t('Error: Failed to upload the Blast query: Wrong format provided for FASTA nucleotide sequence'));
      } else {
         $form_state['qFlag'] = 'upQuery';
      }
   }
   
   $upDB = file_save_upload('DBUPLOAD', array('file_validate_extensions' => array('txt fasta fa fna')), FILE_EXISTS_RENAME);
   if ($upDB) {  
      $upDB_uri = $upDB->uri;
      $form_state['upDB_path'] = drupal_realpath($upDB_uri);
      $upDB_content = file_get_contents($form_state['upDB_path']); 
      if(_validateFasta($upDB_content)){
         form_set_error('DB', t('Error: Failed to upload the Blast subject sequence file: Wrong format provided for FASTA nucleotide sequence'));
      } else {
         $form_state['dbFlag'] = 'upQuery';
      }
   } else {
      $form_state['dbFlag'] = 'blastdb';
   }
      
}

/**
 * Form submition handler for blast_nucleotide_form().
 *
 * @see blast_nucleotide_form_submit()
 */
function blast_nucleotide_form_submit($form, &$form_state) {  
  exec("export BLASTDB=/home/Sequences/blast_dbs/"); 
  $eVal = $form_state['values']['eVal'];
  
  $trgtKey = $form_state['values']['maxTarget'];
  $numAlign = $form['ALG']['GParam']['maxTarget']['#options'][$trgtKey];
  
  $wsKey = $form_state['values']['wordSize'];
  $wordSize = $form['ALG']['GParam']['wordSize']['#options'][$wsKey];
  
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
  
  if ( isset($form_state['qFlag']) ) {
     if ( $form_state['qFlag'] == 'seqQuery' ) {
        $seq_content = $form_state['values']['FASTA'];
	$query = "/tmp/user__query_file.fasta";
        file_put_contents ( $query , $seq_content);
     } elseif ( $form_state['qFlag'] == 'upQuery' ) {
        $query = $form_state['upQuery_path'];
     }
  }
  
  if ( $form_state['dbFlag'] == 'upQuery') {
     $subjectSeq = $form_state['upDB_path'];
     $subSeqOut = drupal_basename($form_state['upDB_path']) . rand(0, 10000);
     $blast_subj_cmd = "blastn -query $query -subject $subjectSeq -out sites/default/files/$subSeqOut.blastn.html -evalue $eVal -word_size $wordSize -gapopen $gapOpen -gapextend $gapExtend -penalty $penalty -reward $reward -num_alignments 100 -html";
     system($blast_subj_cmd);
  } elseif ($form_state['dbFlag'] == 'blastdb') {
     $selected_db = $form_state['values']['SELECT_DB'];
     $blastdb_node = node_load($selected_db);
     $blastdb_path = $blastdb_node->db_path;     
     $blastdb_human_name = $form['DB']['SELECT_DB']['#options'][$selected_db];
     $subSeqOut = $blastdb_human_name . rand(0, 10000);
     $blast_db_cmd = "blastn -task blastn -query $query -db sites/default/files/$blastdb_path -out sites/default/files/$subSeqOut.blastn.html -evalue $eVal -word_size $wordSize -gapopen $gapOpen -gapextend $gapExtend -penalty $penalty -reward $reward -num_alignments 100 -html";
     system($blast_db_cmd,$input);
  }

  $path = "$subSeqOut.blastn.html";
  drupal_goto("blast/report/$path");
}


/**
 * FASTA validating parser
 *
 * @param $sequence 
 *  A string of characters to be validated. A sequence in FASTA format begins with a single-line description, followed by lines of sequence data. 
 *  The description line is distinguished from the sequence data by a greater-than (">") symbol in the first column. 
 *  The word following the ">" symbol is the identifier of the sequence, and the rest of the line is the description (both are optional). 
 *  There should be no space between the ">" and the first letter of the identifier. The sequence ends if another line starting with a ">" appears; 
 *  this indicates the start of another sequence.
 *
 * @return
 *  Return a boolean. 1 if the sequence does not pass the format valifation stage and 0 otherwise.   
 *
 */
function _validateFasta($sequence) {
	$fastaIdRegEx = '/^>.*(\\n|\\r)/';
	$fastaSeqRegEx = '/[^acgntuACGNTU\n\r]/';
	if ( preg_match($fastaSeqRegEx,$sequence) && !(preg_match($fastaIdRegEx,$sequence)) ) {
      $flag = 1;
  } else {
   $flag = 0;
  }
	
  return $flag;
}


/**
 * Load the preexisting blast databases 
 *
 * @return
 *  Return human readble names of the preexisting blast databases   
 *
 */
function _DB_options() {
   $type = 'blastdb';
   $nodes  = node_load_multiple(array(), array('type'=> $type));

   foreach ($nodes as $node) {
      if ( isset($node) ) {
         $options[$node->nid] = $node->db_name;
      }     
   }
   return $options;
}
