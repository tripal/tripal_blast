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
    $form['query'] = [
      '#type' => 'hidden',
      '#value' => $query
    ];

    $form['program'] = [
      '#type' => 'hidden',
      '#value' => $program
    ];

    $form['program_name'] = [
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
        // # FIELD: SHOW EXAMPLE
        // Checkbox to show an example.
        $form['new_blast']['query']['fld_checkbox_example_sequence'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Show an Example Sequence'),
          '#ajax' => [
            'callback' => '::ajaxShowExampleSequenceCallback',
            'wrapper'  => 'wrapper-ajax-fasta-textarea',
            'method'   => 'replace',
            'effect'   => 'fade',
            'progress' => 'throbber',
            'message'  => ''
          ],
          '#prefix' => '<div id="wrapper-checkbox-example-sequence">',
          '#suffix' => '</div>',
        ];
     
        //
        // # FIELD: FASTA
        // Textfield for submitting a mult-FASTA query
        $form['new_blast']['query']['fld_text_fasta'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Enter FASTA sequence(s)'),
          '#description' => $this->t('Enter query sequence(s) in the text area.'),
          // '#default_value' => $defaults['FASTA'],
          '#prefix' => '<div id="wrapper-ajax-fasta-textarea">',
          '#suffix' => '</div>',
        ];


      

      











    //////
    
    $blast_db = $database_service->getDatabaseByType($program);
    

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
    $fld_name_show_example = 'fld_checkbox_example_sequence';
    $fld_value_show_example = $form_state->getValue($fld_name_show_example);

    if ($fld_value_show_example) {
      $form['new_blast']['query']['fld_text_fasta']['#value'] = 1;
    }

    return $form['new_blast']['query']['fld_text_fasta'];
  }
}