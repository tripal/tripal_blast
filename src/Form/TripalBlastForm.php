<?php
/**
 * @file 
 * Construct Tripal BLAST form. 
 */

namespace Drupal\tripal_blast\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Url;

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
  public function buildForm(array $form, FormStateInterface $form_state, $query = NULL, $program = NULL) {    
    // Attach library - JS and Style.
    $form['#attached']['library'][] = 'tripal_blast/tripal-blast-programs';

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

    // Attach a service:
    $database_service = \Drupal::service('tripal_blast.database_service');
    
    // Set the title to be more Researcher friendly.
    $program_name = $database_service->getProgramName($query, $program);
    $page_title = [
      '@query' => ucfirst($query), 
      '@program' => ucfirst($program), 
      '@name' => $program_name
    ];
    $form['#title'] = $this->t('@query to @program BLAST (@name)', $page_title);
    
    // Add the details about the specific BLAST choosen.
    $form['fld_hidden_query'] = [
      '#type' => 'hidden',
      '#value' => $query
    ];

    $form['fld_hidden_program'] = [
      '#type' => 'hidden',
      '#value' => $program
    ];

    $form['fld_hidden_program_name'] = [
      '#type' => 'hidden',
      '#value' => $program_name
    ];

    // # FIELDSET/DETAILS: MAIN.
    $form['new_blast'] = [
      '#type' => 'details',
      '#title' => $this->t('Request a New BLAST'),
      '#open' => TRUE,
    ];
      
      $form['new_blast']['query'] = [
        '#type' => 'details',
        '#title' => $this->t('Enter %type Query Sequence', ['%type' => $type]),
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
        $form['new_blast']['query']['fld_checkbox_example_sequence'] = [
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
        $form['new_blast']['query']['fld_text_fasta'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Enter FASTA sequence(s)'),
          '#description' => $this->t('Enter query sequence(s) in the text area.'),
          // '#default_value' => $defaults['FASTA'],
          '#prefix' => '<div id="tripal-blast-wrapper-ajax-fasta-textarea">',
          '#suffix' => '</div>',
        ];
      
      $config_target_upload = \Drupal::config('tripal_blast.settings')
        ->get('tripal_blast_config_upload.allow_target');
      if ($config_target_upload) {
        $note_target_upload = '&nbsp;You can also use the browse button to upload a file from your local disk. 
          The file may contain a single sequence or a list of sequences.';
      }

      $note_target_upload.
      $form['new_blast']['db'] = [
        '#type' => 'details',
        '#title' => $this->t('Choose Search Target'),
        '#open' => TRUE,
        '#description' => $this->t('Choose from one of the %type BLAST databases listed below.' 
          . $note_target_upload, ['%type' => $query])
      ];  
          
          //
          // # FIELD: SELECT DATABASE.
          $blast_db = $database_service->getDatabaseByType($program);
          $form['new_blast']['db']['fld_select_db'] = [
            '#type' => 'select',
            '#title' => $this->t('%type BLAST Databases:', ['%type' => ucfirst($query)]),
            '#options' => $blast_db,
            '#empty_option' => t('Select a Dataset'),
            '#default_value' => reset($blast_db),
          ];
          
          // Allow target upload - allow target configuration set to TRUE.
          if ($config_target_upload) {
            $form['#attributes']['enctype'] = 'multipart/form-data';  
            //
            // # FIELD: FILE UPLOAD.
            $form['new_blast']['db']['fld_file_db'] = [
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

      // Advanced Options
      // These options will be different depending upon the program selected.
      // Therefore, allow for program-specific callbacks to populate these options.
      $container = 'advanced_options';
      $form['new_blast'][ $container ] = [
        '#type' => 'details',
        '#title' => $this->t('Advanced Options'),
        '#open' => FALSE
      ];
      
      $service_key = 'tripal_blast.program_' . strtolower($program_name);
      $programs_service = \Drupal::service($service_key);
      $programs_service->formOptions($program_name);
      

      

      











    //////
    
    
    

    //var_dump($blast_program);
    return $form;
  }

  /**
   * {@inheritdoc}
   * Save configuration.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }













  /**
   * AJAX callback, update FASTA text field to contain
   * example FASTA sequence.
   */
  public function ajaxShowExampleSequenceCallback(array &$form, FormStateInterface $form_state) {
    $type = $form_state->getValue('fld_hidden_query');
    
    // Fetch FASTA example sequence from configruation.
    $sequence_example = \Drupal::config('tripal_blast.settings')
      ->get('tripal_blast_config_sequence.' .  $type);

    // CHECKBOX:
    $fld_name_show_example = 'fld_checkbox_example_sequence';
    $fld_value_show_example = $form_state->getValue($fld_name_show_example);

    // FASTA FIELD:
    $fld_name_fasta = 'fld_text_fasta';
    
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
    $form['new_blast']['query'][$fld_name_fasta]['#value']  = $fld_value;
    $form['new_blast']['query'][$fld_name_fasta]['#suffix'] = $fld_note;

    return $form['new_blast']['query']['fld_text_fasta'];
  }
}