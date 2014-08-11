<?php

/**
 * @file
 * Contains all functions for the Nucleotide BLAST
 */


/**
 * Nucleotide BLAST Submission Form
 *
 * @see blast_nucleotide_form_validate()
 * @see blast_nucleotide_form_submit()
 */
function blast_nucleotide_form($form, &$form_state) {
	//  CSS support to the form
	$form['#attached']['css'] = array(
		drupal_get_path('module', 'blast_ui') . '/css/form.css',
		);

  // NUCLEOTIDE QUERY
  //.........................

  $form['query'] = array(
    '#type' => 'fieldset',
    '#title' => t('Enter Query Sequence'),
    '#description' => t('Enter one or more queries in the top text box or use the browse button to upload a file from your local disk. The file may contain a single sequence or a list of sequences. In both cases, the data must be in FASTA format. <a href="http://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml" target="_blank">More information..</a> '),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['query']['example_sequence'] = array(
    '#type' => 'button',
    '#button_type'=> 'button',
    '#limit_validation_errors' => array(),
    '#value' => t('Example Sequence'),
    '#prefix' => '<div class="center">',
    '#suffix' => '</div>',
    '#validate' => array(),
    '#ajax' => array(
    	'callback' => 'ajax_nucleotide_text_area_callback',
    	'wrapper'  => 'fasta_seq',
        'method'   => 'replace',
        'effect'   => 'fade',
        ),
	'#attributes' => array('onclick' => 'return false;'),
  );

  // Textfield for submitting a mult-FASTA query
  $form['query']['FASTA'] = array(
    '#type' => 'textarea',
    '#title' => t('Enter FASTA sequence(s)'),
    '#description'=>t('Enter query sequence(s) in the text area.'),
    '#prefix' => '<div id="fasta_seq">',
    '#suffix' => '</div>',
  );

  // Upload a file as an alternative to enter a query sequence
  $form['#attributes']['enctype'] = 'multipart/form-data';
  $form['query']['UPLOAD'] = array(
    '#title' => 'Or upload your own query FASTA:	',
    '#type' => 'managed_file',
    '#description' => t('The file should be a plain-text FASTA
(.fasta, .fna, .fa) file. In other words, it cannot have formatting as is the
case with MS Word (.doc, .docx) or Rich Text Format (.rtf). It cannot be greater
than %max_size in size. <strong>Don\'t forget to press the Upload button before
attempting to submit your BLAST.</strong>',
      array(
        '%max_size' => round(file_upload_max_size() / 1024 / 1024,1) . 'MB'
      )
    ),
    '#upload_validators' => array(
      'file_validate_extensions' => array('fasta fna fa'),
      'file_validate_size' => array(file_upload_max_size()),
    ),
  );


  // BLAST DATABASE
  //.........................

  $form['DB'] = array(
    '#type' => 'fieldset',
    '#title' => t('Choose Search Set'),
    '#description' => t('Choose from one of the nucleotide BLAST databases listed below. You can also use the browse button to upload a file from your local disk. The file may contain a single sequence or a list of sequences. '),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $options = get_blast_database_options('n');
  $form['DB']['SELECT_DB'] = array(
    '#type' => 'select',
    '#title' => t('Nucleotide BLAST Databases:'),
    '#options' => $options,
    '#default_value' => 0,
  );

  // Upload a file as an alternative to selecting an existing BLAST database
  $form['#attributes']['enctype'] = 'multipart/form-data';
  $form['DB']['DBUPLOAD'] = array(
    '#title' => 'Or upload your own dataset:	',
    '#type' => 'managed_file',
    '#description' => t('The file should be a plain-text FASTA
(.fasta, .fna, .fa) file. In other words, it cannot have formatting as is the
case with MS Word (.doc, .docx) or Rich Text Format (.rtf). It cannot be greater
than %max_size in size. <strong>Don\'t forget to press the Upload button before
attempting to submit your BLAST.</strong>',
      array(
        '%max_size' => round(file_upload_max_size() / 1024 / 1024,1) . 'MB'
      )
    ),
    '#upload_validators' => array(
      'file_validate_extensions' => array('fasta fna fa'),
      'file_validate_size' => array(file_upload_max_size()),
    ),
  );

  // ALGORITHM PARAMETERS
  //.........................

  $form['ALG'] = array(
   '#type' => 'fieldset',
   '#title' => t('Algorithm parameters'),
   '#collapsible' => TRUE,
   '#collapsed' => TRUE,
  );

  // General parameters

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

  // Submit
  $form['submit'] = array(
    '#type' => 'submit',
    '#default_value' => ' BLAST ',
  );

  return $form;
}

/**
 * Form validation handler for blast_nucleotide_form().
 *
 * @see blast_nucleotide_form().
 */
function blast_nucleotide_form_validate($form, &$form_state) {

  // Validate Query
  //----------------
  // @todo: We are currently not validating uploaded files are valid FASTA.
  // First check to see if we have an upload & if so then validate it.
  $file = file_load($form_state['values']['UPLOAD']);
  // If the $file is populated then this is a newly uploaded, temporary file.
  if (is_object($file)) {
    $form_state['qFlag'] = 'upQuery';
    $form_state['upQuery_path'] = drupal_realpath($file->uri);
  }
  // Otherwise there was no file uploaded.
  // Check if there was a query sequence entered in the texfield.
  elseif (!empty($form_state['input']['FASTA'])) {
    // Check to ensure that the query sequence entered is valid FASTA.
    if (_validateFasta($form_state['input']['FASTA'])){
      form_set_error('nBLAST', t('You need to provide a valid FASTA sequence
for the query.'));
    }
    else {
      $form_state['qFlag'] = 'seqQuery';
    }
  }
  // Otherwise they didn't enter a query!!
  else {
    form_set_error('query', t('No query sequence given. Only raw sequence or
sequence of type FASTA can be read. Enter sequence in the box provided or
upload a plain text file.'));
  }

  // Validate Database
  //-------------------
  // @todo: We are currently not validating uploaded files are valid FASTA.
  // First check to see if we have an upload & if so then validate it.
  $file = file_load($form_state['values']['DBUPLOAD']);
  // If the $file is populated then this is a newly uploaded, temporary file.
  if (is_object($file)) {
    $form_state['dbFlag'] = 'upDB';
    $form_state['upDB_path'] = drupal_realpath($file->uri);
  }
  // Otherwise there was no file uploaded
  // Check if there was a database choosen from the list instead
  elseif (!empty($form_state['values']['SELECT_DB'])) {
    $form_state['dbFlag'] = 'blastdb';
  }
  // Otherwise they didn't select a database!!
  else {
    form_set_error('DB', t('No database selected. Either choose a database from the list or upload one of your own.'));
  }

}

/**
 * Form submission handler for blast_nucleotide_form().
 *
 * @see blast_nucleotide_form().
 */
function blast_nucleotide_form_submit($form, &$form_state) {

  $error = FALSE;

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

  // If the query was submitted via the texrfield then create a file containing it
  if ( isset($form_state['qFlag']) ) {
    if ( $form_state['qFlag'] == 'seqQuery' ) {
      $seq_content = $form_state['values']['FASTA'];
	    $query = '/tmp/' . date('YMd_His') . '_query.fasta';
      file_put_contents ( $query , $seq_content);
    }
    elseif ( $form_state['qFlag'] == 'upQuery' ) {
      $query = $form_state['upQuery_path'];
    }
  }

  // If the BLAST database was uploaded then use it to run the BLAST
  if ( $form_state['dbFlag'] == 'upDB') {

    // Since we only support using the -db flag (not -subject) we need to create a
    // blast database for the FASTA uploaded.
    // NOTE: We can't support subject because we need to generate the ASN.1+ format
    // to provide multiple download type options from the same BLAST
    $blastdb_with_path = $form_state['upDB_path'];
    $result = NULL;
    exec("makeblastdb -in $blastdb_with_path -dbtype nucl -parse_seqids 2>&1", $result);

    // Check that the BLAST database was made correctly.
    $result = implode('<br />', $result);
    if (preg_match('/Error/', $result)) {
      drupal_set_message('Unable to generate a BLAST database from your uploaded
FASTA sequence. Please check that your file is a valid FASTA file and that if
your sequence headers include pipes (i.e.: | ) they adhere to '
. l('NCBI standards.', 'http://www.ncbi.nlm.nih.gov/books/NBK21097/table/A632/?report=objectonly', array('attributes' => array('target' => '_blank'))),
        'error'
      );

      $error = TRUE;
    }

  }
  // Otherwise, we are using one of the website provided BLAST databases so form the
  // BLAST command accordingly
  elseif ($form_state['dbFlag'] == 'blastdb') {

    $selected_db = $form_state['values']['SELECT_DB'];
    $blastdb_node = node_load($selected_db);
    $blastdb_with_path = $blastdb_node->db_path;

  }

  // Actually submit the BLAST Tripal Job
  // NOTE: Tripal jobs needs to be executed from the command-line before it will be run!!
  $blastdb_with_suffix = $blastdb_with_path . '.nsq';
  if (is_readable($blastdb_with_suffix)) {
    global $user;
    $output_filestub = date('YMd_His');
    $job_args = array(
      'program' => 'blastn',
      'query' => $query,
      'database' => $blastdb_with_path,
      'output_filename' => $output_filestub,
      'options' => array(
        'evalue' => $eVal,
        'word_size' => $wordSize,
        'gapopen' => $gapOpen,
        'gapextend' => $gapExtend,
        'penalty' =>  $penalty,
        'reward' => $reward
      )
    );
    $job_id = tripal_add_job("BLAST (blastn): $query",'blast_job','run_BLAST_tripal_job', $job_args, $user->uid);

    // Redirect to the BLAST results page
    drupal_goto("blast/report/$job_id");
  }
  // We check if $error is set to TRUE because if so then the error has already
  // been reported.
  elseif (!$error) {
    $dbfile_uploaded_msg = ($form_state['dbFlag'] == 'upDB') ? 'The BLAST database was submitted via user upload.' : 'Existing BLAST Database was chosen';
    tripal_report_error(
      'blast_ui',
      TRIPAL_ERROR,
      "BLAST database %db unaccessible. $dbfile_uploaded_msg",
      array('%db' => $blastdb_with_path)
    );
    drupal_set_message('BLAST database unaccessible. Please contact the site administrator.','error');
  }
}


// call back function for example sequence
function ajax_nucleotide_text_area_callback($form, $form_state) {
 $element = $form['query']['FASTA']; // Get example Protein sequence

$element['#value'] =  '>lipoxygenase Glyma15g03040
TTTCGTATGA GATTAAAATG TGTGAAATTT TGTTTGATAG GACATGGGAA AGGAAAAGTT GGAAAGGCTA CAAATTTAAG AGGACAAGTG TCGTTACCAA CCTTGGGAGC TGGCGAAGAT GCATACGATG TTCATTTTGA ATGGGACAGT GACTTCGGAA TTCCCGGTGC ATTTTACATT AAGAACTTCA TGCAAGTTGA GTTCTATCTC AAGTCTCTAA CTCTCGAAGA CATTCCAAAC CACGGAACCA TTCACTTCGT ATGCAACTCC TGGGTTTACA ACTCAAAATC CTACCATTCT GATCGCATTT TCTTTGCCAA CAATGTAAGC TACTTAAATA CTGTTATACA TTGTCTAACA TCTTGTTAGA GTCTTGCATG ATGTGTACCG TTTATTGTTG TTGTTGAACT TTACCACATG GCATGGATGC AAAAGTTGTT ATACACATAA ATTATAATGC AGACATATCT TCCAAGCGAG ACACCGGCTC CACTTGTCAA GTACAGAGAA GAAGAATTGA AGAATGTAAG AGGGGATGGA ACTGGTGAGC GCAAGGAATG GGATAGGATC TATGATTATG ATGTCTACAA TGACTTGGGC GATCCAGATA AGGGTGAAAA GTATGCACGC CCCGTTCTTG GAGGTTCTGC CTTACCTTAC CCTCGCAGAG GAAGAACCGG AAGAGGAAAA ACTAGAAAAG GTTTCTCACT AGTCACTAAT TTATTACTTT TTAATGTTTG TTTTTAGGCA TCTTTTCTGA TGAAATGTAT ACTTTTGATG TTTTTTTGTT TTAGCATAAC TGAATTAGTA AAGTGTGTTG TGTTCCTTAG AAGTTAGAAA AGTACTAAGT ATAAGGTCTT TGAGTTGTCG TCTTTATCTT AACAGATCCC AACAGTGAGA AGCCCAGTGA TTTTGTTTAC CTTCCGAGAG ATGAAGCATT TGGTCACTTG AAGTCATCAG ATTTTCTCGT TTATGGAATC AAATCAGTGG CTCAAGACGT CTTGCCCGTG TTGACTGATG CGTTTGATGG CAATCTTTTG AGCCTTGAGT TTGATAACTT TGCTGAAGTG CGCAAACTCT ATGAAGGTGG AGTTACACTA CCTACAAACT TTCTTAGCAA GATCGCCCCT ATACCAGTGG TCAAGGAAAT TTTTCGAACT GATGGCGAAC AGTTCCTCAA GTATCCACCA CCTAAAGTGA TGCAGGGTAT GCTACATATT TTGAATATGT AGAATATTAT CAATATACTC CTGTTTTTAT TCAACATATT TAATCACATG GATGAATTTT TGAACTGTTA TTATTTGGTG AAGTGGATAA GTCTGCATGG ATGACTGATG AAGAATTTGC TAGAGAAACG ATTGCTGGTG TTAATCCAAA TGTCATTAAG ATTCTTGAGG TAACAAACTA CATGCAACCA AAAGCTTGGA TGCACTTTGG TTGTCAAATT TGAGATTGCT AACCTGATGT CATTGTGTGC TAAATATATT CCAGGAGTTC CCACCACGTA GCAAGCTAGA TTCTCAAGCC TACGGTGATC ATACCTCTAT AATAACAAAA CAACACTTGG AGCCTAACTT GGGTGGGCTC ACCGTTGAGC AGGTAATGAT ATTAGTATTT AGTTTCACAT TAAAGTTATT TCACTATAAT GTTTCTGCTA CTGCTTTGTA ACTATTTTTG TTTTTTTATT TTTTTATTGT GATTCCAATG AACAACAATC AACCAATTAA TTAATTAATT AATTATACTT GGTTGAACAG GCTATCCAGA GCAAGAAGTT GTTCATCTTA GATCACCATG ACTATCTCAT TCCATATTTG AGGAAAATAA ATGCAACTAC CACAAAGACT TACGCTACAA GAACCATATT TTTCTTGAAA AGTGATGGAA CCTTGACGCC ATTGGCCATT GAGTTAAGTA AGCCGCATCC TCAGGGTGAA GGATATGGTC CTGTTAGCGA AGTCTACGTG CCTTCGAGCG AGGGAGTTGA AGCTTACATT TGGTTACTGG CAAAGGCTTA TGTTGTTGTG AATGATTCGT GCTACCATCA ACTCGTCAGC CACTGGTATG ATATTGTCAA TTGGCAATGA GATGCATACA ATTAAATTTG CCAATATATT TATAAATAGA TGATTAACAT ATTAACGTGG ATGGCAGGCT AAACACTCAT GCGGTTGTTG AGCCATTCGT CATAGCAACA AACAGGCATC TGAGCGTGGT TCACCCTATT TACAAACTTT TGTTTCCTCA CTATCGTGAC ACCATGAACA TTAATTCACT TGCCCGCAAA TCCTTGGTCA ATGCAGACGG TATTATAGAG AAAACATTCT TGTGGGGTAG GTACTCTTTG GAAATGTCTG CTGTTATTTA CAAGGACTGG GTTTTCACTG ATCAAGCATT GCCTAATGAT CTTGTCAAGA GGTATGTAAT CTTGTAAAGT TTATTAACAA ATTAGAAATA TATATTGCTG AAAAAATAAA GAATCTAAAT CTATTTAAGT CATCAATGAC ATAAGAAGAA AAAAAGGCCT CAGGCTTGAT ATAACTCTTG TGCTAATCAA TGTTATTTGG GTGCTTTTAA TTCAGAGGAG TTGCGGTTAA GGATCCATCT GCTCCCCATG GAGTTCGGCT TTTGATTGAG GACTATCCTT ATGCTTCTGA TGGGCTAGAG ATATGGGATG CCATCAAGTC CTGGGTGCAT GAATATGTCT CATTCTACTA CAAGTCAGAT GCAGCAATTC AACAAGATCC CGAGCTCCAA GCTTGGTGGA AAGAACTTGT CCAAGTGGGT CATGGTGATT TGAAAGATAA GCCATGGTGG CAAAAGATGC AAACTCGTGA AGAGTTGATT GAAGCCTCGG CTACCCTCGT ATGGATCGCT TCAGCCCTTC ATGCAGCTGT TAACTTTGGA CAGTATCCAT ATGGAGGTTT AATCCTAAAC AGGCCAACCA TTAGCAGGAG ATTCATGCCT GAGAAAGGGT CTGCTGAGTA TGCAGCTTTG GCTAAGAACC CTGAGAAGGA GTTTTTGAAG ACTATTACTG GCAAGAAGGA GACCCTCATT GACCTTACAA TTATAGAAAT CTTGTCAAGG CACACATCTG ATGAGTTCTA CCTTGGGGAG AGAGATGGTG GTGACTATTG GACTTCTGAT GCTGGTCCAC TAGAGGCCTT CAAGAGGTTT GGAAAGAAGC TTCAAGAGAT TGAACAAAAG CTTATACAGA AGAACAAGGA CGAGACGTTG AGAAACCGCA GTGGGCCGGC TAAAATGCCT TACACTTTGC TCTATCCTTC TAGTGAGGAG GGATTGACTT TCAGAGGAAT TCCCAACAGT ATCTCCATCT AGGGGCTCTA TGGTTGTTGC AGTACTTTCC CTGGTTTTCA ATAAAAGTGA ATAGAAGGAA TATGATTCCT TCATCTACCT ATGTATGGTT AAGTTATGTA TTTGGGGTGA TTGAATGTTC TCGTGTTTGC TGTTAAGTGT TAATGAAGAA ATTCTGTTTG TATAAATTGC TGTTAAGTGT TCTCGCGCAT GTTTCAACAA TGATAGCTGG CATGGCAAAG TGCAAACTAG AATAAATCAT CATCTTATGT AAACGATTTC CATTACAGCA AGGGTTGTCC TTAAAATTTC ATTGCCCGTG TATTCATATT TTTGCATTGC CTCAAAGTTA CCACTTCCTC ACCTTAGTAT CTATTTTCTT AAAG';
return $element;
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
