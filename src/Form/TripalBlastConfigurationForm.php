<?php
/**
 * @file 
 * This is the controller for Tripal BLAST Configuration form. 
 */

namespace Drupal\tripal_blast\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines TripalBlastConfigurationForm class.
 * Constructs admin pages configuration page.
 * Page is laid out in tabs/task. 
 * @see tripal_blast.links.tasks.yml
 */
class TripalBlastConfigurationForm extends ConfigFormBase {
  const SETTINGS = 'tripal_blast.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'blast_ui_configuration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   * Build form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Configuration/module variables.
    $config = $this->config(static::SETTINGS);
   
    //
    // # GENERAL CONFIGURATIONS:
    $form['general'] = [
      '#type' => 'details',
      '#title' => t('General'),
      '#open' => TRUE,
    ];
  
      $form['general']['fld_text_blast_path'] = [  
        '#type' => 'textfield',
        '#title' => $this->t('Enter the path of the BLAST program'),
        '#description' => $this->t('You can ignore if your $PATH variable is set. 
          Otherwise, enter the absoulte path to bin folder. For example, /opt/blast/2.2.29+/bin/'),
        '#default_value' => $config->get('tripal_blast_config_general.path')
      ];

      $form['general']['fld_text_blast_threads'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Enter the number of CPU threads to use in blast search.'),
        '#description' => $this->t('You can increase the number to reduce the search time. 
          Before you increase, please check your hardware configurations. 
          A value of one(1) can result in a slower search for some programs eg. tblastn.'),
        '#default_value' => $config->get('tripal_blast_config_general.threads')
      ];
    
      $form['general']['fld_text_blast_eval'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Default e-value (Expected Threshold)'),
        '#description' => $this->t('Expected number of chance matches in a random model. 
          This number should be give in a decimal format.'),
        '#default_value' => $config->get('tripal_blast_config_general.eval')
      ];
    
      $form['general']['fld_text_blast_qrange'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Default max matches in a query range'),
        '#description' => $this->t('Limit the number of matches to a query range. 
          This option is useful if many strong matches to one part of a query may prevent 
          BLAST from presenting weaker matches to another part of the query.'),
        '#default_value' => $config->get('tripal_blast_config_general.qrange')
      ];

    
    //
    // # FILE UPLOAD CONFIGURATIONS:  
    $form['file_upload'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Allow File Upload'),
      '#description' => $this->t('The following options allow you to control whether your users can
        upload files for the query or target respectively. The ability to upload files allows
        them to more conviently BLAST large sets of sequences. However, the size of the
        files could be problematic, storage-wise, on your server.<br />')
    ];

      $form['file_upload']['fld_checkbox_blast_query_upload'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Query Sequence Upload'),
        '#description' => $this->t('When checked, a query file upload field will be available on BLAST request forms.'),
        '#default_value' => $config->get('tripal_blast_config_upload.allow_query')
      ];
    
      $form['file_upload']['fld_checkbox_blast_target_upload'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Target Sequence Upload'),
        '#description' => $this->t('When checked, a target file upload field will be available on BLAST request forms.'),
        '#default_value' => $config->get('tripal_blast_config_upload.allow_target')
      ];

    
    //
    // # SEQUENCE CONFIGURATIONS:  
    $form['example_sequence'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => t('Set Example Sequences'),
      '#description' => t('There is the ability to show example sequences built-in to the various 
        BLAST forms. Use the following fields to set these example sequences. 
        This allows you to provide more relevant examples to your users.
        More information: <a href="@fasta-format-url" target="_blank">FASTA format</a>.',
        ['@fasta-format-url' => 'https://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml'])
      ];

      $form['example_sequence']['fld_text_blast_nucleotide_example'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Nucleotide Example'),
        '#description' => $this->t('Enter a complete nucleotide FASTA record including the header.'),
        '#default_value' => $config->get('tripal_blast_config_sequence.nucleotide')
      ];

      $form['example_sequence']['fld_text_blast_protein_example'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Protein Example'),
        '#description' => $this->t('Enter a complete protein FASTA record including the header.'),
        '#default_value' => $config->get('tripal_blast_config_sequence.protein')
      ];

    
    //
    // # JOBS CONFIGURATIONS:  
    $form['protection'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Protect against large jobs'),
      '#description' => $this->t('Depending on the size and nature of your target databases, 
        you may wish to constrain use of this module.'),
    ];

      $form['protection']['fld_text_blast_max_results'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Maximum number of results to show on report page'),
        '#description' => $this->t('If there are more hits that this, the user is 
          able to download but not visualize the results.'),
        '#default_value' => $config->get('tripal_blast_config_jobs.max_result')
      ];
    
    //
    // # NOTIFICATION CONFIGURATIONS:
    $form['notification'] = array(
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Show warning text'),
      '#description' => $this->t('This permits display of a temporary warning message at the top of the
        BLAST input form. Text can include HTML tags. Remember to remove the
        message when it is no longer relevant.'),
    );

      $form['notification']['fld_text_blast_warning_text'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Text to be displayed'),
        '#rows' => 10,
        '#default_value' => $config->get('tripal_blast_config_notification.warning_text')
      );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   * Validate configuration.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate BLAST path.
    $fld_name_blast_path = 'fld_text_blast_path';
    $fld_value_blast_path = $form_state->getValue($fld_name_blast_path);
    $blast_path = $fld_value_blast_path . 'blastn';

    if(!empty($fld_value_blast_path)) {
      if(!file_exists($blast_path) ) {  
        $form_state->setErrorByName('fld_text_blast_path', $this->t('Please enter a valid path not including the name 
          of the blast program (ie: /usr/bin/). You can leave this blank if you have your $PATH variable set appropriately.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   * Save configuration.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Field Values: 
    // GENERAL CONFIGURATIONS:
    $fld_value_blast_path = $form_state->getValue('fld_text_blast_path');
    $fld_value_blast_threads = $form_state->getValue('fld_text_blast_threads');
    $fld_value_blast_eval = $form_state->getValue('fld_text_blast_eval');
    $fld_value_blast_qrange = $form_state->getValue('fld_text_blast_qrange');

    // UPLOAD CONFIGURATIONS:
    $fld_value_blast_query_upload = $form_state->getValue('fld_checkbox_blast_query_upload');
    $fld_value_blast_target_upload = $form_state->getValue('fld_checkbox_blast_target_upload');

    // SEQUENCE CONFIGURATIONS:
    $fld_value_blast_nucleotide_example = $form_state->getValue('fld_text_blast_nucleotide_example');
    $fld_value_blast_protein_example = $form_state->getValue('fld_text_blast_protein_example');

    // JOB CONFIGURATIONS:
    $fld_value_blast_max_results = $form_state->getValue('fld_text_blast_max_results');

    // NOTIFICATION CONFIGURATIONS:
    $fld_value_blast_warning_text = $form_state->getValue('fld_text_blast_warning_text');
    
    // Set defined variables.
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('tripal_blast_config_general.path', $fld_value_blast_path)
      ->set('tripal_blast_config_general.threads', $fld_value_blast_threads)
      ->set('tripal_blast_config_general.eval', $fld_value_blast_eval)
      ->set('tripal_blast_config_general.qrange', $fld_value_blast_qrange)    
      ->set('tripal_blast_config_upload.allow_query', $fld_value_blast_query_upload)
      ->set('tripal_blast_config_upload.allow_target', $fld_value_blast_target_upload)
      ->set('tripal_blast_config_sequence.nucleotide', $fld_value_blast_nucleotide_example)
      ->set('tripal_blast_config_sequence.protein', $fld_value_blast_protein_example)
      ->set('tripal_blast_config_jobs.max_result', $fld_value_blast_max_results)
      ->set('tripal_blast_config_notification.warning_text', $fld_value_blast_warning_text)      
      ->save();

    return parent::submitForm($form, $form_state);
  }
}