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

  // Add a warning, if need be (to be used for temporary messages like down-for-maintanence)
  if ($warning = variable_get('blast_ui_warning_text', '')) {
    $form['warning'] = array(
      '#markup' => t($warning),
      '#prefix' => '<div class="messages warning">',
      '#suffix' => '</div>',
    );
  }

  // CSS support to the form
  $form['#attached']['css'] = array(
    drupal_get_path('module', 'blast_ui') . '/theme/css/form.css',
  );

  // We are going to lay out this form as two choices: either look at a recent blast
  // or execute a new one. We want to theme accordingly so set a class to aid us in such.
  $form['#attributes'] = array('class' => array('blast-choice-form'));

  // Determine some defaults.
  $defaults = array(
    'FASTA' => NULL,
    'SELECT_DB' => NULL,
  );

  // Edit and Resubmit functionality.
  // We want to pull up the details from a previous blast and fill them in as defaults
  // for this blast.
  // @todo: handle file uploads better; currently for the query we put the file contents
  // in the text area causing reupload and we simply do not support re-using of an uploaded target.
  if (isset($_GET['resubmit'])) {
    $prev_blast = get_BLAST_job(blast_ui_reveal_secret($_GET['resubmit']));

    // First of all warn if the uploaded their search target last time
    // since we don't support that now.
    if (!isset($prev_blast->blastdb->nid)) {
      drupal_set_message('You will need to re-upload your <em>Search Target</em> database.','warning');
    }
    // And if they didn't upload a target then set a default for the select list.
    else {
      $defaults['SELECT_DB'] = $prev_blast->blastdb->nid;
    }

    // Finally set a default for the query. Since we don't support defaults for file uploads,
    // we need to get the contents of the file and put them in our textarea.
    if (is_readable($prev_blast->files->query)) {
      $defaults['FASTA'] = file_get_contents($prev_blast->files->query);
    }
    // There should always be a query file (both if uploaded or not) so if we cant find it
    // then it must have been cleaned up :-( -- warn the user.
    else {
      drupal_set_message('Unable to retrieve previous query sequence; please re-upload it.', 'error');
    }

    // Finally save the previous blast details for use by the advanced option forms.
    $form_state['prev_blast'] = $prev_blast;
  }

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

  // CHOOSE RECENT BLAST RESULTS
  //-----------------------------------
  // If there are recent jobs then show a table of them.
  if (get_number_of_recent_jobs()) {

    $form['A'] = array(
      '#type' => 'fieldset',
      '#title' => 'See Results from a Recent BLAST',
      '#attributes' => array('class' => array('blast-choice')),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE
    );

    $form['A']['job_table'] = array(
      '#type' => 'markup',
      '#markup' => theme('blast_recent_jobs', array($blast_program)),
    );
  }

  // REQUEST A NEW BLAST
  //-----------------------------------
  $form['B'] = array(
    '#type' => 'fieldset',
    '#title' => 'Request a New BLAST',
    '#attributes' => array('class' => array('blast-choice')),
    '#collapsible' => TRUE,
  );

  // NUCLEOTIDE QUERY
  //.........................
  $form['B']['query'] = array(
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
  $form['B']['query']['example_sequence'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show an Example Sequence'),
    '#prefix' => '<span style="float: right;">',
    '#suffix' => '</span>',
    '#ajax' => array(
      'callback' => 'ajax_blast_ui_perprogram_example_sequence_callback',
      'wrapper'  => 'fasta-textarea',
      'method'   => 'replace',
      'effect'   => 'fade',
    ),
  );

  // Textfield for submitting a mult-FASTA query
  $form['B']['query']['FASTA'] = array(
    '#type' => 'textarea',
    '#title' => t('Enter FASTA sequence(s)'),
    '#description'=>t('Enter query sequence(s) in the text area.'),
    '#default_value' => $defaults['FASTA'],
    '#prefix' => '<div id="fasta-textarea">',
    '#suffix' => '</div>',
  );

  if (variable_get('blast_ui_allow_query_upload', TRUE)) {
    // Upload a file as an alternative to enter a query sequence
    $form['#attributes']['enctype'] = 'multipart/form-data';
    $form['B']['query']['UPLOAD'] = array(
      '#title' => 'Or upload your own query FASTA:  ',
      '#type' => 'managed_file',
      '#description' => t('The file should be a plain-text FASTA
  (.fasta, .fna, .fa, .fas) file. In other words, it cannot have formatting as is the
  case with MS Word (.doc, .docx) or Rich Text Format (.rtf). It cannot be greater
  than %max_size in size. <strong>Don\'t forget to press the Upload button before
  attempting to submit your BLAST.</strong>',
        array(
          '%max_size' => round(file_upload_max_size() / 1024 / 1024,1) . 'MB'
        )
      ),
      '#upload_validators' => array(
        'file_validate_extensions' => array('fasta fna fa fas'),
        'file_validate_size' => array(file_upload_max_size()),
      ),
    );
  }


  // BLAST DATABASE
  //.........................
  $target_upload_text = '';
  if (variable_get('blast_ui_allow_target_upload', FALSE)) {
    $target_upload_text = 'You can also use the browse button to upload a '
                        . 'file from your local disk. The file may contain '
                        . 'a single sequence or a list of sequences. ';
  }
  $form['B']['DB'] = array(
    '#type' => 'fieldset',
    '#title' => t('Choose Search Target'),
    '#description' => t('Choose from one of the %type BLAST databases listed '
                        . 'below. ' . $target_upload_text,
      array('%type' => $db_type)),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $options = get_blast_database_options($db_type);
  $form['B']['DB']['SELECT_DB'] = array(
    '#type' => 'select',
    '#title' => t('%type BLAST Databases:', array('%type' => ucfirst($db_type))),
    '#options' => $options,
    '#empty_option' => t('Select a Dataset'),
    '#default_value' => $defaults['SELECT_DB'],
  );

  if (variable_get('blast_ui_allow_target_upload', FALSE)) {
    // Upload a file as an alternative to selecting an existing BLAST database
    $form['#attributes']['enctype'] = 'multipart/form-data';
    $form['B']['DB']['DBUPLOAD'] = array(
      '#title' => 'Or upload your own dataset:  ',
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
      '#default_value' => variable_get($db_file_id, ''),
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

  $form['B']['ALG'] = array(
   '#type' => 'fieldset',
   '#title' => t('Advanced Options'),
   '#collapsible' => TRUE,
   '#collapsed' => TRUE,
  );

  $advanced_options_form = 'blast_ui_' . $blast_program . '_advanced_options_form';
  if (function_exists($advanced_options_form)) {
    call_user_func_array($advanced_options_form, array(&$form, $form_state));
  }
  $form['B']['ALG'] = array_merge($form['B']['ALG'], $form['ALG']);
  unset($form['ALG']);

  // Submit
  //.........................
  $form['B']['submit'] = array(
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
  $file = null;
  if(isset($form_state['values']['UPLOAD'])) {
    $file = file_load($form_state['values']['UPLOAD']);
  }
  // If the $file is populated then this is a newly uploaded, temporary file.
  if (is_object($file)) {
    $form_state['qFlag'] = 'upQuery';
    $form_state['upQuery_path'] = drupal_realpath($file->uri);
  }
  // Otherwise there was no file uploaded.
  // Check if there was a query sequence entered in the texfield.
  elseif (!empty($form_state['input']['FASTA'])) {
    // Check to ensure that the query sequence entered is valid FASTA.
    if (!validate_fasta_sequence($type, $form_state['input']['FASTA'])){
      form_set_error('query', t('You need to provide a valid %molecule-type FASTA sequence '
      . 'for the query. For more information see the '
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

}//blast_ui_per_blast_program_form_validate


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
  }
  else {
    $mdb_type = 'prot';
  }

  // We want to save information about the blast job to the database for recent jobs &
  // edit and resubmit functionality.
  // First set defaults.
  $blastjob = array(
    'job_id' => NULL,
    'blast_program' => $form_state['values']['blast_program'],
    'target_blastdb' => (isset($form_state['values']['SELECT_DB'])) ? $form_state['values']['SELECT_DB'] : NULL,
    'target_file' => NULL,
    'query_file' => NULL,
    'result_filestub' => NULL,
    'options' => serialize(array())
  );

  // QUERY
  //-------------------------
  // BLAST can only take the query as a file;
  // therefore, if it was submitted via the textfield we need to create a file containing
  // the submitted sequence.
  if (isset($form_state['qFlag'])) {
    if ($form_state['qFlag'] == 'seqQuery') {
      $seq_content = $form_state['values']['FASTA'];
      $blastjob['query_file'] = variable_get('file_temporary_path', file_directory_temp()) . '/' . date('YMd_His') . '_query.fasta';
      file_put_contents ($blastjob['query_file'], $seq_content);
    }
    elseif ($form_state['qFlag'] == 'upQuery') {
      $blastjob['query_file'] = $form_state['upQuery_path'];
    }
  }

  // TARGET
  //-------------------------
  // If the BLAST database was uploaded then we need to format it to make it compatible with blast.
  if ($form_state['dbFlag'] == 'upDB') {

    // Since we only support using the -db flag (not -subject) we need to create a
    // blast database for the FASTA uploaded.
    // NOTE: We can't support subject because we need to generate the ASN.1+ format
    // to provide multiple download type options from the same BLAST
    $blastdb_with_path = $form_state['upDB_path'];
    $result = NULL;
    exec('makeblastdb -in ' . escapeshellarg($blastdb_with_path) . ' -dbtype ' . escapeshellarg($mdb_type) . ' -parse_seqids 2>&1', $result);

    // Check that the BLAST database was made correctly.
    $result = implode('<br />', $result);
    if (preg_match('/Error/', $result)) {
      drupal_set_message(t('Unable to generate a BLAST database from your uploaded FASTA '
                          .'sequence. Please check that your file is a valid FASTA file '
                          .'and that if your sequence headers include pipes (i.e.: | ) '
                          .' they adhere to ')
                          .l('NCBI standards.',
                             'http://www.ncbi.nlm.nih.gov/books/NBK21097/table/A632/?report=objectonly',
                             array('attributes' => array('target' => '_blank'))
                          ),
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
    $blastdb_name = $blastdb_node->db_name;
    $blastdb_with_path = $blastdb_node->db_path;
  }

  $blastjob['target_file'] = $blastdb_with_path;

  // Determine the path to the blast database with extension.
  if ($mdb_type == 'nucl' && (preg_match('/\.[pn]al/', $blastdb_with_path) == 0)) {
    // Suffix may be .nsq or .nal
    if (is_readable("$blastdb_with_path.nsq")) {
      $blastdb_with_suffix = "$blastdb_with_path.nsq";
    }
    else if (is_readable("$blastdb_with_path.nal")) {
      $blastdb_with_suffix = "$blastdb_with_path.nal";
    }
  }
  else if ($mdb_type == 'prot' && (preg_match('/\.[pn]al/', $blastdb_with_path) == 0)) {
    // Suffix may be .psq or .pal
    if (is_readable("$blastdb_with_path.psq")) {
      $blastdb_with_suffix = "$blastdb_with_path.psq";
    }
    else if (is_readable("$blastdb_with_path.pal")) {
      $blastdb_with_suffix = "$blastdb_with_path.pal";
    }
  }
  else {
    $blastdb_with_suffix = $blastdb_with_path;
  }

  if (!is_readable($blastdb_with_suffix)) {
    $error = TRUE;

    $dbfile_uploaded_msg = ($form_state['dbFlag'] == 'upDB')
        ? 'The BLAST database was submitted via user upload.'
        : 'Existing BLAST Database was chosen.';
    tripal_report_error(
      'blast_ui',
      TRIPAL_ERROR,
      "BLAST database %db unaccessible. %msg",
      array('%db' => $blastdb_with_path, '%msg' => $dbfile_uploaded_msg)
    );
    $msg = "$dbfile_uploaded_msg BLAST database '$blastdb_with_path' is unaccessible. ";
    $msg .= "Please contact the site administrator.";
    drupal_set_message($msg, 'error');
  }

  // ADVANCED OPTIONS
  //-------------------------
  // Now let each program process its own advanced options.
  $advanced_options = array();
  $advanced_options_form_submit = 'blast_ui_' . $blast_program . '_advanced_options_form_submit';
  if (function_exists($advanced_options_form_submit)) {
    $advanced_options = call_user_func_array(
      $advanced_options_form_submit,
      array($form['B'], $form_state)
    );
  }
  else {
    $advanced_options = array('none' => 0);
  }

  $blastjob['options'] = serialize($advanced_options);

  // SUBMIT JOB TO TRIPAL
  //-------------------------
  // Actually submit the BLAST Tripal Job
  if (!$error) {
    // BLAST target exists.
    global $user;

    // We want to save all result files (.asn, .xml, .tsv, .html) in the public files directory.
    // Usually [drupal root]/sites/default/files.
    $output_dir = tripal_get_files_dir('tripal_blast');
    $output_filestub = $output_dir . DIRECTORY_SEPARATOR . date('YMd_His') . '.blast';

    $job_args = array(
      'program' => $blast_program,
      'query' => $blastjob['query_file'],
      'database' => $blastdb_with_path,
      'output_filename' => $output_filestub,
      'options' => $advanced_options
    );

    $job_id = tripal_add_job(
      t('BLAST (@program): @query', array('@program' => $blast_program, '@query' => $blastjob['query_file'])),
      'blast_job',
      'run_BLAST_tripal_job',
      $job_args,
      $user->uid
    );

    $blastjob['result_filestub'] = $output_filestub;
    $blastjob['job_id'] = $job_id;

    // SAVE JOB INFO
    //-------------------------
    drupal_write_record('blastjob', $blastjob);

    //Encode the job_id
    $job_encode_id = blast_ui_make_secret($job_id);

    // RECENT JOBS
    //-------------------------
    if (!isset($_SESSION['blast_jobs'])) {
      $_SESSION['blast_jobs'] = array();
    }
    $_SESSION['blast_jobs'][] = $job_encode_id;

    // NOTE: Originally there was a call to tripal_launch_jobs() here. That should
    // NEVER be done since it runs possibly long jobs in the page load causing time-out
    // issues. If you do not want to run tripal jobs manually, look into installing
    // Tripal daemon which will run jobs as they're submitted or set up a cron job to
    // launch the tripal jobs on a specified schedule.

    // Redirect to the BLAST results page
    drupal_goto("blast/report/$job_encode_id");
  }


}

/**
 * AJAX: Replace the sequence textarea with one containing an example.
 */
function ajax_blast_ui_perprogram_example_sequence_callback($form, $form_state) {
  $sequence_type = $form_state['values']['query_type'];

  // Choose the example sequence based on the sequence type of the query.
  if ($sequence_type == 'nucleotide') {
    $example_sequence = variable_get('blast_ui_nucleotide_example_sequence', 'sample');
    if ($example_sequence == 'sample') {
      $example_sequence = '>partial lipoxygenase Glyma15g03040
TTTCGTATGA GATTAAAATG TGTGAAATTT TGTTTGATAG GACATGGGAA
AGGAAAAGTT GGAAAGGCTA CAAATTTAAG AGGACAAGTG TCGTTACCAA
CCTTGGGAGC TGGCGAAGAT GCATACGATG TTCATTTTGA ATGGGACAGT
GACTTCGGAA TTCCCGGTGC ATTTTACATT AAGAACTTCA TGCAAGTTGA
GTTCTATCTC AAGTCTCTAA CTCTCGAAGA CATTCCAAAC CACGGAACCA
TTCACTTCGT ATGCAACTCC TGGGTTTACA ACTCAAAATC CTACCATTCT
GATCGCATTT TCTTTGCCAA CAATGTAAGC TACTTAAATA CTGTTATACA
TTGTCTAACA TCTTGTTAGA GTCTTGCATG ATGTGTACCG TTTATTGTTG
TTGTTGAACT TTACCACATG GCATGGATGC AAAAGTTGTT ATACACATAA
ATTATAATGC AGACATATCT TCCAAGCGAG ACACCGGCTC CACTTGTCAA
GTACAGAGAA GAAGAATTGA AGAATGTAAG AGGGGATGGA ACTGGTGAGC
GCAAGGAATG GGATAGGATC TATGATTATG ATGTCTACAA TGACTTGGGC
GATCCAGATA AGGGTGAAAA GTATGCACGC CCCGTTCTTG GAGGTTCTGC
CTTACCTTAC CCTCGCAGAG GAAGAACCGG AAGAGGAAAA ACTAGAAAAG
GTTTCTCACT AGTCACTAAT TTATTACTTT TTAATGTTTG TTTTTAGGCA
TCTTTTCTGA TGAAATGTAT ACTTTTGATG TTTTTTTGTT TTAGCATAAC
TGAATTAGTA AAGTGTGTTG TGTTCCTTAG AAGTTAGAAA AGTACTAAGT
ATAAGGTCTT TGAGTTGTCG TCTTTATCTT AACAGATCCC AACAGTGAGA
AGCCCAGTGA TTTTGTTTAC CTTCCGAGAG ATGAAGCATT TGGTCACTTG
AAGTCATCAG ATTTTCTCGT TTATGGAATC AAATCAGTGG CTCAAGACGT
CTTGCCCGTG TTGACTGATG CGTTTGATGG CAATCTTTTG AGCCTTGAGT
TTGATAACTT TGCTGAAGTG CGCAAACTCT ATGAAGGTGG AGTTACACTA
CCTACAAACT TTCTTAGCAA GATCGCCCCT ATACCAGTGG TCAAGGAAAT
TTTTCGAACT GATGGCGAAC AGTTCCTCAA GTATCCACCA CCTAAAGTGA
TGCAGGGTAT GCTACATATT TTGAATATGT AGAATATTAT CAATATACTC
CTGTTTTTAT TCAACATATT TAATCACATG GATGAATTTT TGAACTGTTA';
      tripal_set_message(t('You can set the example sequence through the administrative interface: <a href="@url" target="_blank">Admin Toolbar > Tripal > Extensions > Tripal BLAST User Interface</a>',
        array('@url' => url('admin/tripal/extension/tripal_blast'))));
    }
  }
  elseif ($sequence_type == 'protein') {
    $example_sequence = variable_get('blast_ui_protein_example_sequence', 'sample');
    if ($example_sequence == 'sample') {
      $example_sequence = '>gi|166477|gb|AAA96434.1| resveratrol synthase [Arachis hypogaea]
MVSVSGIRKVQRAEGPATVLAIGTANPPNCIDQSTYADYYFRVTNSEHMTDLKKKFQRICERTQIKNRHM
YLTEEILKENPNMCAYKAPSLDAREDMMIREVPRVGKEAATKAIKEWGQPMSKITHLIFCTTSGVALPGV
DYELIVLLGLDPCVKRYMMYHQGCFAGGTVLRLAKDLAENNKDARVLIVCSENTAVTFRGPSETDMDSLV
GQALFADGAAAIIIGSDPVPEVEKPIFELVSTDQKLVPGSHGAIGGLLREVGLTFYLNKSVPDIISQNIN
DALNKAFDPLGISDYNSIFWIAHPGGRAILDQVEQKVNLKPEKMKATRDVLSNYGNMSSACVFFIMDLMR
KRSLEEGLKTTGEGLDWGVLFGFGPGLTIETVVLRSVAI';
      tripal_set_message(t('You can set the example sequence through the administrative interface: <a href="@url" target="_blank">Admin Toolbar > Tripal > Extensions > Tripal BLAST User Interface</a>',
        array('@url' => url('admin/tripal/extension/tripal_blast'))));
    }
  }
  else {
    $example_sequence = 'unknown query type';
  }

  // If the Show Example checkbox is true then put the example in the textfield
  if ($form_state['values']['example_sequence']) {

    // Set the value to be the example sequence (set in the admin form).
    $form['B']['query']['FASTA']['#value'] = $example_sequence;
  }
  // Otherwise we want to remove the already displayed example.
  else {
    $form['B']['query']['FASTA']['#value'] = '';
  }

  return $form['B']['query']['FASTA'];
}
