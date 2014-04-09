<?php

function blast_nucleotide_form($form, &$form_state) {
  
//Nucleotide BLAST

$form['nBLAST'] = array(
    '#type' => 'fieldset',
    '#title' => t('Standard Nucleotide BLAST'),
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
  	'#description' => t('Please give a text a FASTA file, not a MS-Word or other document, you can upload up to 10 Mb.'),
  );


//DATABASE//

$form['DB'] = array(
  '#type' => 'fieldset',
  '#title' => t('BLAST Assembled KnowPulse Genomes'),
  '#collapsible' => TRUE, 
  '#collapsed' => FALSE,  
);
  
//$options =  _DB_options(); 
$form['DB']['SELECT_DB'] = array(
 '#type' => 'select',
 '#title' => t('KnowPulse Databases:'),
 '#options' => 'TEST',
 '#default_value' => NULL,
);
  
// Upload a file as an alternative to enter a query sequence 
$form['#attributes']['enctype'] = 'multipart/form-data';
$form['DB']['DBUPLOAD'] = array(
   '#prefix' => 'Or upload your own dataset:	',
  	'#type' => 'file',
  	'#description' => t('Please give a text or a FASTA file, not a MS-Word or other document, you can upload up to 10 Mb.'),
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

function blast_nucleotide_form_validate($form, &$form_state) {
   $fastaSeq = $form_state['input']['FASTA'];
   if (isset($fastaSeq)) {
      if(_validateFasta($fastaSeq)){
          form_set_error('nBLAST', t('Error: Failed to read the Blast query: Wrong format provided for FASTA nucleotide sequence'));
      } else {
         $form_state['flag'] = 'seqQuery';
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
   }
   
}

function blast_nucleotide_form_submit($form, &$form_state) {
   
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
        $query = $form_state['values']['FASTA'];
     } elseif ( $form_state['qFlag'] == 'upQuery' ) {
        $query = $form_state['upQuery_path'];
     }
  }
  
  if ( isset($form_state['dbFlag']) ) {
     if ( $form_state['dbFlag'] == 'upQuery') {
        $subjectSeq = $form_state['upDB_path'];
        $subSeqOut = drupal_basename($form_state['upDB_path']);
        //shell_exec("blastn -query $query -subject $subjectSeq -out /tmp/$subSeqOut.blastn -evalue $eVal -word_size $wordSize -gapopen $gapOpen -gapextend $gapExtend -penalty $penalty -reward $reward -num_alignments $numAlign -outfmt 7 ");
        shell_exec("blastn -query $query -subject $subjectSeq -out /home/amm072/kp-amir/sites/default/files/tripal/tripal_blast/$subSeqOut.blastn.html -evalue $eVal -word_size $wordSize -gapopen $gapOpen -gapextend $gapExtend -penalty $penalty -reward $reward -num_alignments 100 -html");
     } else {
        $db = $form['values']['SELECT_DB'];
        //$output = shell_exec("blastn -query $query -db $db -out <> -evalue $eVal -word_size <> -gapopen <> -gapextend <> -penalty <> -reward <>");
     }
  }


}

// 
// output file name 

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

function _DB_options() {
  $type = 'BLASTDB';
  $nodes  = node_load_multiple(array(), array('type' => $type));
  return $nodes;

}
