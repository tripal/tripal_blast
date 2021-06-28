<?php
/**
 * @file 
 * Construct Tripal BLAST form. 
 */

namespace Drupal\tripal_blast\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\tripal\Services\TripalJob;

use Drupal\tripal_blast\Services\TripalBlastProgramHelper;

/**
 * Define Tripal BLAST form.
 */
class TripalBlastForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tripalblastform';
  }

  /**
   * {@inheritdoc}
   * Build form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $query = NULL, $db = NULL) {     
    // Add a warning, if need be (to be used for temporary message like down-for-maintenance).
    $config_warning_text = \Drupal::config('tripal_blast.settings')
      ->get('tripal_blast_config_notification.warning_text');

    if ($config_warning_text) {
      $form['config_warning'] = [
        '#type' => 'inline_template',
        '#theme' => 'theme-tripal-blast-message',
        '#data' => [
          'message' => $config_warning_text,
          'type' => 'warning'
        ]
      ];
    }

    // Attach library - service, style and JS.
    $form['#attached']['library'][] = 'tripal_blast/tripal-blast-programs';
    $db_service = \Drupal::service('tripal_blast.database_service');
    $job_service = \Drupal::service('tripal_blast.job_service');
    
    // Set the BLAST variables.
    $query_type = $query;
    $db_type = $db;
    $blast_program = $db_service->getProgramName($query, $db);
    
    $form['query_type'] = [
      '#type' => 'hidden',
      '#value' => $query_type,
    ];

    $form['db_type'] = [
      '#type' => 'hidden',
      '#value' => $db_type,
    ];

    $form['blast_program'] = [
      '#type' => 'hidden',
      '#value' => $blast_program,
    ];


    // Defaults.
    $defaults = [
      'FASTA' => NULL,
      'SELECT_DB' => NULL
    ];

    // Edit and Resubmit functionality.
    // We want to pull up the details from a previous blast and fill them in as defaults
    // for this blast.
    
    // @todo: handle file uploads better; currently for the query we put the file contents
    // in the text area causing reupload and we simply do not support re-using of an uploaded target.
    $resubmit = $query = \Drupal::request()->query->get('resubmit') ?? NULL; 
    if ($resubmit) {
      $job_id = $job_service->jobsBlastRevealSecret($resubmit);
      $prev_blast = $job_service->jobsGetJobByJobId($job_id);
    
      if (!isset($prev_blast->blastdb->nid)) {
        // First of all warn if the uploaded their search target last time
        // since we don't support that now.                
        $form['reupload_warning'] = [
          '#type' => 'inline_template',
          '#theme' => 'theme-tripal-blast-message',
          '#data' => [
            'message' => 'You will need to re-upload your Search Target database.',
            'type' => 'warning'
          ]
        ];
      }    
      else {
        // And if they didn't upload a target then set a default for the select list.
        $defaults['SELECT_DB'] = $prev_blast->blastdb->nid;
      }

      // Finally set a default for the query. Since we don't support defaults for file uploads,
      // we need to get the contents of the file and put them in our textarea.
      if (is_readable($prev_blast->files->query)) {
        $defaults['FASTA'] = file_get_contents($prev_blast->files->query);
      }
      else {
        // There should always be a query file (both if uploaded or not) so if we cant find it
        // then it must have been cleaned up :-( -- warn the user.
        $form['reupload_warning'] = [
          '#type' => 'inline_template',
          '#theme' => 'theme-tripal-blast-message',
          '#data' => [
            'message' => 'Unable to retrieve previous query sequence; please re-upload it.',
            'type' => 'error'
          ]
        ];        
      }

      // Finally save the previous blast details for use by the advanced option forms.
      $form_state['prev_blast'] = $prev_blast;
    }
    

    // Set the title to be more Researcher friendly.
    $page_title = [
      '@query' => ucfirst($query_type), 
      '@program' => ucfirst($db_type), 
      '@name' => $blast_program
    ];
    $form['#title'] = $this->t('@query to @program BLAST (@name)', $page_title);
    
    // CHOOSE RECENT BLAST RESULTS
    // ---------------------------
    $jobs = $job_service->jobsCountRecentJobs();
    if ($jobs) {
      $form['A'] = [
        '#type' => 'details',
        '#title' => $this->t('See Results from a Recent BLAST'),
        '#open' => FALSE
      ];
  
      $form['A']['recent_job'] = $job_service->jobsCreateTable([$blast_program]);
    }
    
    // NEW BLAST
    // ---------
    // # FIELDSET/DETAILS: MAIN.
    $form['B'] = [
      '#type' => 'details',
      '#title' => $this->t('Request a New BLAST'),
      '#open' => TRUE,
    ];
       
      // NUCLEOTIDE QUERY
      // ................
      $form['B']['query'] = [
        '#type' => 'details',
        '#title' => $this->t('Enter %type Query Sequence', ['%type' => $query]),
        '#open' => TRUE,
        '#description' => $this->t('Enter one or more queries in the top text box or 
          use the browse button to upload a file from your local disk. The file 
          may contain a single sequence or a list of sequences. In both cases, 
          the data must be in <a href="@formaturl">FASTA format</a>.', 
          ['@formaturl' => 'http://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml']),
      ];
        
        //
        // # FIELD: SHOW EXAMPLE.
        // Checkbox to show an example.
        $form['B']['query']['example_sequence'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Show an Example Sequence'),
          '#ajax' => [
            'callback' => '::ajaxShowExampleSequenceCallback',
            'wrapper'  => 'tripal-blast-wrapper-ajax-fasta-textarea',
            'method'   => 'replace',
            'effect'   => 'fade',
            'progress' => 'throbber',
            'message'  => ''
          ],
          '#prefix' => '<div id="tripal-blast-wrapper-checkbox-example-sequence">',
          '#suffix' => '</div>',
        ];

        //
        // # FIELD: FASTA.
        // Textfield for submitting a mult-FASTA query
        $form['B']['query']['FASTA'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Enter FASTA sequence(s)'),
          '#description' => $this->t('Enter query sequence(s) in the text area.'),
          '#default_value' => $defaults['FASTA'],
          '#prefix' => '<div id="tripal-blast-wrapper-ajax-fasta-textarea">',
          '#suffix' => '</div>',
        ];

        $config_query_upload = \Drupal::config('tripal_blast.settings')
          ->get('tripal_blast_config_upload.allow_query');
        
        $is_query_upload_true = $config_query_upload ?? TRUE;
        if ($is_query_upload_true) {
          // Upload a file as an alternative to enter a query sequence.
          $form['#attributes']['enctype'] = 'multipart/form-data';
          
          //
          // # FIELD: FILE UPLOAD.
          $form['B']['query']['UPLOAD'] = array(
            '#title' => 'Or upload your own query FASTA:  ',
            '#type' => 'managed_file',
            '#description' => $this->t('The file should be a plain-text FASTA
              (.fasta, .fna, .fa, .fas) file. In other words, it cannot have formatting as is the
              case with MS Word (.doc, .docx) or Rich Text Format (.rtf). It cannot be greater
              than %max_size in size. <strong>Don\'t forget to press the Upload button before
              attempting to submit your BLAST.</strong>',
              ['%max_size' => round(file_upload_max_size() / 1024 / 1024,1) . 'MB']
            ),
            '#upload_validators' => array(
              'file_validate_extensions' => array('fasta fna fa fas'),
              'file_validate_size' => array(file_upload_max_size()),
            ),
          );
        }

      // BLAST DATABASE
      // ..............      
      $config_target_upload = \Drupal::config('tripal_blast.settings')
        ->get('tripal_blast_config_upload.allow_target');  
      
      $is_target_upload_true = $config_target_upload ?? FALSE;
      $note_target_upload = ($is_target_upload_true)
        ? '&nbsp;You can also use the browse button to upload a file from your local disk. 
          The file may contain a single sequence or a list of sequences.'
        : '';  

      $form['B']['db'] = [
        '#type' => 'details',
        '#title' => $this->t('Choose Search Target'),
        '#open' => TRUE,
        '#description' => $this->t('Choose from one of the %type BLAST databases listed below.' 
          . $note_target_upload, ['%type' => $query])
      ]; 
       
          //
          // # FIELD: SELECT DATABASE.
          $blast_db = $db_service->getDatabaseByType($db_type);
          $form['B']['db']['SELECT_DB'] = [
            '#type' => 'select',
            '#title' => $this->t('%type BLAST Databases:', ['%type' => ucfirst($query)]),
            '#options' => $blast_db,
            '#empty_option' => t('Select a Dataset'),
            '#default_value' => $defaults['SELECT_DB'],
          ];

          // Allow target upload - allow target configuration set to TRUE.
          if ($config_query_upload) {
            $form['#attributes']['enctype'] = 'multipart/form-data';  
            
            //
            // # FIELD: FILE UPLOAD.
            $form['B']['db']['DBUPLOAD'] = [
              '#title' => $this->t('Or upload your own dataset:'),
              '#type' => 'managed_file',
              '#description' => t('The file should be a plain-text FASTA (.fasta, .fna, .fa) file. 
                In other words, it cannot have formatting as is the case with MS Word (.doc, .docx) 
                or Rich Text Format (.rtf). It cannot be greater than %max_size in size. 
                <strong>Don\'t forget to press the Upload button before attempting to submit your BLAST.</strong>',
                ['%max_size' => round(file_upload_max_size() / 1024 / 1024,1) . 'MB']),
              '#upload_validators' => [
                'file_validate_extensions' => ['fasta fna fa'],
                'file_validate_size' => [file_upload_max_size()]
              ]
            ];
          }
      
      // ADVANCED OPTIONS
      // ................
      // These options will be different depending upon the program selected.
      // Therefore, allow for program-specific callbacks to populate these options.
      // Load program specific service that creates advanced option fields.
      $service_key = 'tripal_blast.program_' . $blast_program;
      $programs_service = \Drupal::service($service_key);
      
      $form_alter = $programs_service->formOptions([]);
      array_push($form['B'], $form_alter);
      unset($form_alter);
    
    $form['B']['submit'] = [
      '#type' => 'submit',
      '#default_value' => ' BLAST ',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   * Validate BLAST request.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $blast_program = $form_state->getValue('blast_program');
    $query_type = $form_state->getValue('query_type');
    $db_type = $form_state->getValue('db_type');

    $molecule_type = ($query_type == 'nucleotide') ?  'nucleotide' : 'amino acid residues';
    
    // VALIDATE QUERY
    // --------------
    // @todo: We are currently not validating uploaded files are valid FASTA.
    // First check to see if we have an upload & if so then validate it.
    $file = null;
    $fld_file_query_value = $form_state->getValue('UPLOAD');

    if($fld_file_query_value) {
      $file = file_load($fld_file_query_value);
    }

    $fld_fasta_value = $form_state->getValue('FASTA');
    if (is_object($file)) {
      // If the $file is populated then this a newly uploaded, temporary file.
      $form_state->setValue('qFlag', 'upQuery');
      
      $file_uri = \Drupal::service('file_system')->realpath($file->uri);
      $form_state->setValue('upQuery_path', $file_uri);
    }
    elseif (!empty($fld_fasta_value)) {
      // Otherwise there was no file uploaded.
      // Check if there was a query sequence entered in the texfield.
      $is_valid_fasta = TripalBlastProgramHelper::programValidateFastaSequence($query_type, $fld_fasta_value);

      if ($is_valid_fasta) {
        $form_state->setValue('qFlag', 'seqQuery');
      }
      else {
        // Check to ensure that the query sequence entered is valid FASTA.
        // ERROR.
        $form_state->setErrorByName('query', $this->t('The file should be a plain-text FASTA
          (.fasta, .fna, .fa, .fas) file. In other words, it cannot have formatting as is the
          case with MS Word (.doc, .docx) or Rich Text Format (.rtf). It cannot be greater
          than %max_size in size. <strong>Don\'t forget to press the Upload button before
          attempting to submit your BLAST.</strong>', ['@max_size' => round(file_upload_max_size() / 1024 / 1024,1) . 'MB']));
      }
    }
    else {
      // Otherwise they didn't enter a query!!
      // ERROR.
      $form_state->setErrorByName('query', $this->t('No query sequence given. 
        Only raw sequence or sequence of type FASTA can be read. 
        Enter sequence in the box provided or upload a plain text file.'));
    }

    // VALIDATE DATABASE
    // -----------------
    // @todo: We are currently not validating uploaded files are valid FASTA.
    // First check to see if we have an upload & if so then validate it.
    $fld_file_db_value = $form_state->getValue('DBUPLOAD');
    $fld_select_db_value = $form_state->getValue('SELECT_DB');

    if ($fld_file_db_value) {
      $file = file_load($fld_file_db_value);

      if (is_object($file)) {
        // If the $file is populated then this is a newly uploaded, temporary file.
        $form_state->setValue('dbFlag', 'upDB');

        $file_uri = \Drupal::service('file_system')->realpath($file->uri);
        $form_state->setValue('upDB_path', $file_uri);
      }
      elseif (empty($fld_select_db_value)) {
        // Otherwise there was no file uploaded.
        // Check if there was a database choosen from the list instead.
        $form_state->setValue('dbFlag', 'blastdb');
      }
      else {
        // Otherwise they didn't select a database!!
        // ERROR.
        $form_state->setErrorByName('db', $this->t('No database selected. Either choose a database
          from the list or upload one of your own.'));
      }
    }
    elseif (!empty($fld_select_db_value)) {
      // Otherwise there was no file uploaded.
      // Check if there was a database choosen from the list instead.
      $form_state->setValue('dbFlag', 'blastdb');

    }
    else {
      // Otherwise they didn't select a database!!
      // ERROR.
      $form_state->setErrorByName('db', $this->t('No database selected. Either choose a database
        from the list or upload one of your own.'));
    }

    // VALIDATE ADVANCED OPTIONS
    // -------------------------
    $service_key = 'tripal_blast.program_' . $blast_program;
    $programs_service = \Drupal::service($service_key);
    $validate_fields = $programs_service->formFieldNames();

    foreach($validate_fields as $field_name => $field_validators) {
      $fld_value = $form_state->getValue($field_name);
      
      if (count($field_validators) > 0) {
        foreach($field_validators as $validator) {
          $is_valid = TripalBlastProgramHelper::programValidateValue($fld_value, $validator);

          if($is_valid['result'] == FALSE) {
            $form_state->setErrorByName($field_name, $this->t($is_valid['message']));
          }
        }
      }
    } 
  }

  /**
   * {@inheritdoc}
   * Save/Create a BLAST job request.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $error = FALSE;
    
    $blast_program = $form_state->getValue('blast_program');
    $query_type = $form_state->getValue('query_type');
    $db_type = $form_state->getValue('$db_type');

    $mdb_type = ($db_type == 'nucleotide') ? 'nucl' : 'prot';
    
    $fld_select_db_value = $form_state->getValue('SELECT_DB');
    $db = $fld_select_db_value ?? NULL; 

    // We want to save information about the blast job to the database for recent jobs &
    // edit and resubmit functionality.
    // First set defaults.
    $blastjob = [
      'job_id' => NULL,
      'blast_program' => $blast_program,
      'target_blastdb' => $db,
      'target_file' => NULL,
      'query_file' => NULL,
      'result_filestub' => NULL,
      'options' => serialize([])
    ];
    
    // QUERY
    // -----
    // BLAST can only take the query as a file;
    // therefore, if it was submitted via the textfield we need to create a file containing
    // the submitted sequence.
    $var_qflag_value = $form_state->getValue('qFlag') ?? NULL;
    if ($var_qflag_value) {
      if ($var_qflag_value == 'seqQuery') {
        $seq_content = $form_state->getValue('FASTA');
        
        $query_file = \Drupal::service('file_system')->getTempDirectory() . '/' . date('YMd_His') . '_query.fasta';
        $blastjob['query_file'] = $query_file;

        file_put_contents ($blastjob['query_file'], $seq_content);
      }
      elseif ($var_qflag_value == 'upQuery') {
        $blastjob['query_file'] = $form_state->getValue('upQuery_path');
      }
    }

    // TARGET
    // ------    
    $var_dbflag_value = $form_state->getValue('dbFlag');
    if ($var_dbflag_value == 'upDB') {
      // If the BLAST database was uploaded then we need to format it to make it compatible with blast.

      // Since we only support using the -db flag (not -subject) we need to create a
      // blast database for the FASTA uploaded.
      // NOTE: We can't support subject because we need to generate the ASN.1+ format
      // to provide multiple download type options from the same BLAST
      $blastdb_with_path = $form_state->getValue('upDB_path');
      
      $config_blast_path = \Drupal::config('tripal_blast.settings')
        ->get('tripal_blast_config_general.path');
      
      $makeblast_db = $config_blast_path . 'makeblastdb';
      $result = NULL;
      exec(escapeshellarg($makeblast_db) . ' -in ' . escapeshellarg($blastdb_with_path) . ' -dbtype ' . escapeshellarg($mdb_type) . ' -parse_seqids 2>&1', $result);

      // Check that the BLAST database was made correctly.
      $result = implode('<br />', $result);
      if (preg_match('/Error/', $result)) {
        \Drupal::messenger()->addError($this->t('Unable to generate a BLAST database from your uploaded FASTA sequence. 
          Please check that your file is a valid FASTA file and that if your sequence headers include pipes (i.e.: | ) 
          they adhere to <a href="@ncbi_standard" target="_blank">NCBI Standard</a>.', ['@ncbi_standard' => 'http://www.ncbi.nlm.nih.gov/books/NBK21097/table/A632/?report=objectonly']));

        $error = TRUE;
      }
    }
    elseif ($var_dbflag_value == 'blastdb') {
      // Otherwise, we are using one of the website provided BLAST databases so form the
      // BLAST command accordingly.
      $database_service = \Drupal::service('tripal_blast.database_service');
      $selected_db = $form_state->getValue('SELECT_DB');
      $db_config = $database_service->getDatabaseConfig($selected_db);
  
      $blastdb_name = $db_config['name'];
      $blastdb_with_path = $db_config['path'];
    }  
    
    $blastjob['target_file'] = $blastdb_with_path; 
    // Determine the path to the blast database with extension.
    $blastdb_with_suffix = $blastdb_with_path;
    
    if ($mdb_type == 'nucl') {
      // Suffix may be .nsq or .nal.
      if (is_readable("$blastdb_with_path.nsq")) {
        $blastdb_with_suffix = "$blastdb_with_path.nsq";
      }
      elseif (is_readable("$blastdb_with_path.nal")) {
        $blastdb_with_suffix = "$blastdb_with_path.nal";
      }
    }
    elseif ($mdb_type == 'prot') {
      // Suffix may be .psq or .pal.
      if (is_readable("$blastdb_with_path.psq")) {
        $blastdb_with_suffix = "$blastdb_with_path.psq";
      }
      else if (is_readable("$blastdb_with_path.pal")) {
        $blastdb_with_suffix = "$blastdb_with_path.pal";
      }
    }

    if (!is_readable($blastdb_with_suffix)) {
      //$error = TRUE;
      /*
      $dbfile_uploaded_msg = ($form_state->getValue('dbFlag') == 'upDB')
          ? 'The BLAST database was submitted via user upload.'
          : 'Existing BLAST Database was chosen.';

      tripal_report_error(
        'blast_ui',
        TRIPAL_ERROR,
        "BLAST database %db unaccessible. %msg",
        ['%db' => $blastdb_with_path, '%msg' => $dbfile_uploaded_msg]
      );
  
      $msg = "$dbfile_uploaded_msg BLAST database '$blastdb_with_path' is unaccessible. ";
      $msg .= "Please contact the site administrator.";
      \Drupal::messenger()->addError($this->t($msg)); */
    }
      
    // ADVANCED OPTIONS
    // ----------------
    // Now let each program process its own advanced options.
    $service_key = 'tripal_blast.program_' . $blast_program;
    $programs_service = \Drupal::service($service_key);
    $advanced_field_names = $programs_service->formFieldNames();
    $advanced_field_values = [];

    foreach(array_keys($advanced_field_names) as $field_name) {
      $advanced_field_values[ $field_name ] = $form_state->getValue($field_name);
    }

    $field_value_blast_key = $programs_service->formFieldBlastKey($advanced_field_values);
    $advanced_options = ($field_value_blast_key) ? $field_value_blast_key : ['none' => 0];

    $blastjob['options'] = serialize($advanced_options);

    // SUBMIT JOB TO TRIPAL
    //---------------------
    // Actually submit the BLAST Tripal Job
    if (!$error) {
      // BLAST target exists.

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
        \Drupal::currentUser()->id()
      );
  
      $blastjob['result_filestub'] = $output_filestub;
      $blastjob['job_id'] = $job_id;
      
      // SAVE JOB INFO
      //--------------
      $job_service = \Drupal::service('tripal_blast.job_service');
      $job_service->jobsSave($blastjob);

      //Encode the job_id.
      $job_encode_id = $job_service->jobsBlastMakeSecret($job_id);

      // RECENT JOBS
      //------------
      if (!isset($_SESSION['blast_jobs'])) {
        $_SESSION['blast_jobs'] = [];
      }
      
      $_SESSION['blast_jobs'][] = $job_encode_id;

      // NOTE: Originally there was a call to tripal_launch_jobs() here. That should
      // NEVER be done since it runs possibly long jobs in the page load causing time-out
      // issues. If you do not want to run tripal jobs manually, look into installing
      // Tripal daemon which will run jobs as they're submitted or set up a cron job to
      // launch the tripal jobs on a specified schedule.

      // Redirect to the BLAST results page
      $go = '/blast/report/' . $job_encode_id;
      $redirect = new RedirectResponse(Url::fromUserInput($go)->toString());
      $redirect->send();
    }
  }

  /**
   * AJAX callback in advanced options field.
   */
  public function ajaxFieldUpdateCallback(array &$form, FormStateInterface $form_state) {
    $blast_program = $form_state->getValue('blast_program');
    $mm_set = $form_state->getValue('M&MScores');
    $gap_cost_options = TripalBlastProgramHelper::programGetGapCost($blast_program, $mm_set);

    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand(NULL, 'ajaxFieldUpdateCallback',  [ $gap_cost_options ]));

    return $response;
  }

  /**
   * AJAX callback, update FASTA text field to contain
   * example FASTA sequence.
   */
  public function ajaxShowExampleSequenceCallback(array &$form, FormStateInterface $form_state) {
    $query_type = $form_state->getValue('query_type');
   
    // Fetch FASTA example sequence from configruation.
    $sequence_example = \Drupal::config('tripal_blast.settings')
      ->get('tripal_blast_config_sequence.' .  $query_type);

    // CHECKBOX:
    $fld_name_show_example = 'example_sequence';
    $fld_value_show_example = $form_state->getValue($fld_name_show_example);

    // FASTA FIELD:
    $fld_name_fasta = 'FASTA';
    
    // Checkbox - TRUE or FALSE.
    if ($fld_value_show_example) {
      // Load sample sequence into the value property of the field.
      $fld_value = $sequence_example;

      // Add a note to user, default example may be replaced through the admin interface.
      $l = \Drupal::l('administartive interface', Url::fromRoute('tripal_blast.configuration'));
      $fld_note = '<div class="tripal-blast-tip">' 
        . $this->t('You can set the example sequence through the @note.', ['@note' => $l]) 
        . '</div>';     
    }
    else {
      $fld_value = '';
      $fld_note  = '';      
    }
    
    // Update field value and suffix (add a note/tip).
    $form['B']['query'][$fld_name_fasta]['#value']  = $fld_value;
    $form['B']['query'][$fld_name_fasta]['#suffix'] = $fld_note;

    return $form['B']['query']['FASTA'];
  }
}