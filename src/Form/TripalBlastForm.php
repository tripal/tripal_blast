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
      $form['warning'] = [
        '#type' => 'inline_template',
        '#template' => '
          <div role="contentinfo" aria-label="Warning message" class="messages messages--warning">
            <div role="alert">
              <h2 class="visually-hidden">Warning message</h2>
              '. $config_warning_text .'
            </div>
          </div>
        '
      ];  
    }    

    // Attach library - JS and Style.
    $form['#attached']['library'][] = 'tripal_blast/tripal-blast-programs';

    // Attach a service:
    $database_service = \Drupal::service('tripal_blast.database_service');

    // Set the title to be more Researcher friendly.
    $blast_program = $database_service->getProgramName($query, $db);
    $page_title = [
      '@query' => ucfirst($query), 
      '@program' => ucfirst($db), 
      '@name' => $blast_program
    ];
    $form['#title'] = $this->t('@query to @program BLAST (@name)', $page_title);
    
    // Add the details about the specific BLAST choosen.
    $form['query_type'] = [
      '#type' => 'hidden',
      '#value' => $query
    ];

    $form['db_type'] = [
      '#type' => 'hidden',
      '#value' => $db
    ];

    $form['blast_program'] = [
      '#type' => 'hidden',
      '#value' => $blast_program
    ];

     
    // @TODO: get jobs.
    // CHOOSE RECENT BLAST RESULTS
    // ---------------------------
    if ($x) {
      $form['A'] = [
        '#type' => 'details',
        '#title' => $this->t('See results from a recent BLAST'),
        '#open' => FALSE
      ];
      
      // @TODO: theme blast jobs.
      $form['A']['job_title'] = [
        '#type' => '',
      ];
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
          // '#default_value' => $defaults['FASTA'],
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
          $blast_db = $database_service->getDatabaseByType($db);
          $form['B']['db']['SELECT_DB'] = [
            '#type' => 'select',
            '#title' => $this->t('%type BLAST Databases:', ['%type' => ucfirst($query)]),
            '#options' => $blast_db,
            '#empty_option' => t('Select a Dataset'),
            '#default_value' => reset($blast_db),
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
      $service_key = 'tripal_blast.program_' . $blast_program;
      $programs_service = \Drupal::service($service_key);
      $programs_service->setProgramName($blast_program);

      $form_alter = $programs_service->formOptions($blast_program);
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
      $is_valid_fasta = TripalBlastProgramHelper::programValidateFastaSequence($query_type, $fld_value_fasta);

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
    $db_type = $form_state->getValue('db_type');

    $mdb_type = ($program == 'nucleotide') ? 'nucl' : 'prot';
    
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
      // BLAST command accordingly
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
    
    \Drupal::messenger()->addError($this->t(print_r($blastjob)));
  }

  /**
   * 
   */
  public function ajaxFieldUpdateCallback(array &$form, FormStateInterface $form_state) {
    $blast_program = $form_state->getValue('blast_program');
    $mm_set = $form_state->getValue('M&MScores');
    $gap_cost_options = TripalBlastProgramHelper::programGetGapCost($blast_program, $mm_set);

    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand(NULL, 'ajaxFieldUpdateCallback', [ $gap_cost_options ]));

    return $response;
  }

  /**
   * AJAX callback, update FASTA text field to contain
   * example FASTA sequence.
   */
  public function ajaxShowExampleSequenceCallback(array &$form, FormStateInterface $form_state) {
    $type = $form_state->getValue('query_type');
    
    // Fetch FASTA example sequence from configruation.
    $sequence_example = \Drupal::config('tripal_blast.settings')
      ->get('tripal_blast_config_sequence.' .  $type);

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