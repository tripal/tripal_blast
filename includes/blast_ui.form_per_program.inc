<?php

/**
 * @file
 * Forms in the file provide a per BLAST program interface to submitting BLASTs.
 *
 * In other words, it provides a form for blastn, one for blastp, etc. It does
 * this using a single form function for code reusability and depending upon
 * the $type passed in, it will execute additional hooks allowing for program
 * specific modifications & advanced options.
 */

/**
 * This single form definition provides 4 different program-specific forms.
 */
function blast_ui_per_blast_program_form($form, $form_state) {

	//  CSS support to the form
	$form['#attached']['css'] = array(
		drupal_get_path('module', 'blast_ui') . '/theme/css/form.css',
	);

  // Determine the BLAST program.
  $query_type = $form_state['build_info']['args'][0];
  $db_type = $form_state['build_info']['args'][1];
  if ($query_type == 'nucleotide') {
    if ($db_type == 'nucleotide') {
      $blast_program = 'blastn';
    }
    elseif ($db_type == 'protein') {
      $blast_program = 'blastx';
    }
  }
  elseif ($query_type == 'protein') {
    if ($db_type == 'nucleotide') {
      $blast_program = 'tblastn';
    }
    elseif ($db_type == 'protein') {
      $blast_program = 'blastp';
    }
  }

  // Set the title to be more Researcher friendly.
  drupal_set_title(t(
    '@query to @db BLAST (@program)',
    array(
      '@query' => ucfirst($query_type),
      '@db' => ucfirst($db_type),
      '@program' => $blast_program
    )
  ));

  // Add the details about the specific BLAST choosen.
  $form['query_type'] = array(
    '#type' => 'hidden',
    '#value' => $query_type
  );
  $form['db_type'] = array(
    '#type' => 'hidden',
    '#value' => $db_type
  );
  $form['blast_program'] = array(
    '#type' => 'hidden',
    '#value' => $blast_program
  );

  // NUCLEOTIDE QUERY
  //.........................

  $form['query'] = array(
    '#type' => 'fieldset',
    '#title' => t('Enter %type Query Sequence',
      array('%type' => ucfirst($query_type))),
    '#description' => t('Enter one or more queries in the top text box or use '
      . 'the browse button to upload a file from your local disk. The file may '
      . 'contain a single sequence or a list of sequences. In both cases, the '
      . 'data must be in <a href="@formaturl" target="_blank">FASTA format</a>.',
        array(
          '@formaturl' => 'http://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml'
        )
      ),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  // Checkbox to show an example.
  $form['query']['example_sequence'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show an Example Sequence'),
    '#prefix' => '<span style="float: right;">',
    '#suffix' => '</span>',
    '#ajax' => array(
    	'callback' => 'ajax_blast_ui_example_sequence_callback',
    	'wrapper'  => 'fasta-textarea',
      'method'   => 'replace',
      'effect'   => 'fade',
    ),
  );

  // Textfield for submitting a mult-FASTA query
  $form['query']['FASTA'] = array(
    '#type' => 'textarea',
    '#title' => t('Enter FASTA sequence(s)'),
    '#description'=>t('Enter query sequence(s) in the text area.'),
    '#prefix' => '<div id="fasta-textarea">',
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
    '#description' => t('Choose from one of the %type BLAST databases listed '
      . 'below. You can also use the browse button to upload a file from your '
      . 'local disk. The file may contain a single sequence or a list of '
      . 'sequences. ',
      array('%type' => $db_type)),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $options = get_blast_database_options($db_type);
  $form['DB']['SELECT_DB'] = array(
    '#type' => 'select',
    '#title' => t('%type BLAST Databases:', array('%type' => ucfirst($db_type))),
    '#options' => $options,
    '#default_value' => 0,
  );

  if (variable_get('blast_ui_allow_target_upload', FALSE)) {
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
  }

  // Advanced Options
  //.........................
  // These options will be different depending upon the program selected.
  // Therefore, allow for program-specific callbacks to populate these options.

  $form['ALG'] = array(
   '#type' => 'fieldset',
   '#title' => t('Advanced Options'),
   '#collapsible' => TRUE,
   '#collapsed' => TRUE,
  );

  $advanced_options_form = 'blast_ui_' . $blast_program . '_advanced_options_form';
  if (function_exists($advanced_options_form)) {
    call_user_func_array($advanced_options_form, array(&$form, $form_state));
  }

  // Submit
  //.........................
  $form['submit'] = array(
    '#type' => 'submit',
    '#default_value' => ' BLAST ',
  );

  return $form;
}

/**
 * Validate the user options submitted via the above form.
 *
 * @see blast_ui_per_blast_program_form().
 */
function blast_ui_per_blast_program_form_validate($form, &$form_state) {


  $blast_program = $form_state['values']['blast_program'];

  $type = $form_state['values']['query_type'];
  if ($type == 'nucleotide') {
    $molecule_type = 'nucleotides';
  }
  else {
    $molecule_type = 'amino acid residues';
  }

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
    if (validate_fasta_sequence($type, $form_state['input']['FASTA'])){
      form_set_error('query', t('You need to provide a valid FASTA sequence '
      . 'for the query. It should contain a FASTA header/definition line '
      . 'followed by %molecule-type. For more information see the '
      . '<a href="@url" target="_blank">NCBI FASTA Specification</a>.',
        array(
          '%molecule-type' => $molecule_type,
          '@url' => 'http://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml'
      )));
    }
    else {
      $form_state['qFlag'] = 'seqQuery';
    }
  }
  // Otherwise they didn't enter a query!!
  else {
    form_set_error('query', t('No query sequence given. Only raw sequence or '
    . 'sequence of type FASTA can be read. Enter sequence in the box provided '
    . 'or upload a plain text file.'));
  }

  // Validate Database
  //-------------------
  // @todo: We are currently not validating uploaded files are valid FASTA.
  // First check to see if we have an upload & if so then validate it.
  if (isset($form_state['values']['DBUPLOAD'])) {
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
      form_set_error('DB', t('No database selected. Either choose a database '
      . 'from the list or upload one of your own.'));
    }
  }
  // Otherwise there was no file uploaded
  // Check if there was a database choosen from the list instead
  elseif (!empty($form_state['values']['SELECT_DB'])) {
    $form_state['dbFlag'] = 'blastdb';
  }
  // Otherwise they didn't select a database!!
  else {
    form_set_error('DB', t('No database selected. Either choose a database '
    . 'from the list or upload one of your own.'));
  }

  // Validate Advanced Options
  //---------------------------
  $advanced_options_form_validate = 'blast_ui_' . $blast_program . '_advanced_options_form_validate';
  if (function_exists($advanced_options_form_validate)) {
    call_user_func_array(
      $advanced_options_form_validate,
      array(&$form, $form_state)
    );
  }

}

/**
 * Process the user options submitted via the blast program form.
 *
 * @see blast_ui_per_blast_program_form().
 */
function blast_ui_per_blast_program_form_submit($form, &$form_state) {

  $error = FALSE;
  $blast_program = $form_state['values']['blast_program'];

    if ($form_state['values']['db_type'] == 'nucleotide') {
      $mdb_type = 'nucl';
      $file_suffix = '.nsq';
    }
    else {
      $mdb_type = 'prot';
      $file_suffix = '.psq';
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
    exec("makeblastdb -in $blastdb_with_path -dbtype $mdb_type -parse_seqids 2>&1", $result);

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

  // Now let each program process it's own advanced options.
  $advanced_options = array();
  $advanced_options_form_submit = 'blast_ui_' . $blast_program . '_advanced_options_form_submit';
  if (function_exists($advanced_options_form_submit)) {
    $advanced_options = call_user_func_array(
      $advanced_options_form_submit,
      array($form, &$form_state)
    );
  }

  // Actually submit the BLAST Tripal Job
  // NOTE: Tripal jobs needs to be executed from the command-line before it will be run!!
  $blastdb_with_suffix = $blastdb_with_path . $file_suffix;
  if (is_readable($blastdb_with_suffix)) {
    global $user;
    $output_filestub = date('YMd_His');
    $job_args = array(
      'program' => $blast_program,
      'query' => $query,
      'database' => $blastdb_with_path,
      'output_filename' => $output_filestub,
      'options' => $advanced_options
    );
    $job_id = tripal_add_job(
      t('BLAST (@program): @query', array('@program' => $blast_program, '@query' => $query)),
      'blast_job',
      'run_BLAST_tripal_job',
      $job_args,
      $user->uid
    );
		// Encode job ID
		$job_encode_id = base64_encode($job_id);
    // Redirect to the BLAST results page
    drupal_goto("blast/report/$job_encode_id");
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