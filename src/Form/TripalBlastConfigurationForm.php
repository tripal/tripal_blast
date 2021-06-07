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
    // # VISUALIZATION CONFIGURATIONS:
    // If this is set to TRUE (allow civit visualization), set this fieldset/details
    // to open = TRUE (expanded).
    $config_cvitjs = $config->get('tripal_blast_config_visualization.civitjs');

    $form['visualization'] = [
      '#type' => 'details',
      '#open' => $config_cvitjs,
      '#title' => $this->t('Enable and configure genome visualization'),
      '#description' => $this->t('The JavaScript program CViTjs enables users to see BLAST hits on an
        entire genome assembly. See the help tab for information on how to download and set up CViTjs')
    ];
      
      $absolute_cvitjs_data_path = DRUPAL_ROOT . '/sites/all/libraries/cvitjs/data';
      $form['visualization']['explanation'] = [
        '#type' => 'inline_template',
        '#template' => '
          <div role="contentinfo" aria-label="Warning message" class="messages messages--warning">
            <div role="alert">
              <h2 class="visually-hidden">Warning message</h2>
              ViTjs is only applicable for genome BLAST targets. After it is enabled here, 
              CViTjs will need to be enabled for each applicable BLAST target node.
            </div>
          </div>
          <div role="contentinfo" aria-label="Status message" class="messages messages--status">
            <div role="alert">
              <h2 class="visually-hidden">Warning message</h2>              
              <strong>CViTjs Data Location: ' . $absolute_cvitjs_data_path . '</strong>
              <br />The GFF3 and Genome Target-specific CViTjs configuration files should be located
              at the above system path. Feel free to organize this directory further.
              See the "Help" tab for more information.
            </div>
          </div>'
      ];

      $form['visualization']['fld_checkbox_blast_cvitjs_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable CViTjs'),
        '#description' => $this->t('When checked, CViTjs will be enabled.'),
        '#default_value' => $config_cvitjs,
      ];
    
      // Get CViTjs confuration text, if possible.
      // @TODO blast_ui_get_cvit_conf_text() goes in '' the condition below.
      $default_value = ''; // blast_ui_get_cvit_conf_text()
      if (!$default_value) {
        $default_value = 'Unable to get CViTjs configuration information. 
You will need to enable CViTjs and set and save the path to CViTjs before 
you can edit the CViTjs configuration text.';

        $disabled = TRUE;
      }
      else {
        $disabled = FALSE;
      }

      $form['visualization']['fld_text_blast_cvitjs_config'] = [
        '#type' => 'textarea',
        '#title' => $this->t('CViTjs configuration'),
        '#description' => $this->t('This is the contents of the file that defines data directories and 
          backbone GFF files for each genome assembly target. It is named
          cvit.conf and is in the root directory for the CViTjs javascript code.
          This is NOT the config file that is used to build the display for each
          individual genome. See the help tab for more information about configuration files.'),
        '#rows' => 10,
        '#disabled' => $disabled,
        '#default_value' => $default_value
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
    
    /* CVIT JS LOCATION/PATH referenced in this validation does not correspond to 
       a form field above (form build).

    // Check path to CViTjs executable and make sure cvit.conf is writable
    $fld_name_cvitjs_enabled = 'fld_checkbox_blast_cvitjs_enabled';
    $fld_value_cvitjs_enabled = getValue($fld_name_cvitjs_enabled);

    if ($fld_value_cvitjs_enabled) {
      $cvit_path = ''; //@TODO: blast_ui_get_cvit_conf();

      if (!$cvit_path || !file_exists($cvit_path)) {
        $form_state->setErrorByName('fld_text_cvitjs_path', $this->t('The CViTjs configuration file, cvit.conf, 
          does not exist at the path given (@cvitjs_path). Please check your path. If you have not yet downloaded 
          CViTjs, see the help tab for more information.', 
          ['@cvitjs_path' => $fld_value_cvitjs_enabled]));        
      }

      if (!is_writable($cvit_path)) {
        $form_state->setErrorByName('fld_text_cvitjs_path', $this->t('The file $cvit_path is not writable by this page.
          Please enable write access for apache then try saving these settings again.'));
      }
    }
    */ 
    
    // Validate contents of cvitjs configuration text.
    $is_config_disabled = $form['visualization']['fld_text_blast_cvitjs_config']['#disabled'];

    if (!$is_config_disabled) {
      // Do this validation only when the textfield (cvitjs config) allows for data.
      $fld_name_cvitjs_config = 'fld_text_blast_cvitjs_config';
      $fld_value_cvitjs_config = $form_state->getValue($fld_name_cvitjs_config);

      if ($fld_value_cvitjs_config && !preg_match('/\[general\]\s*\ndata_default =.*/m', $fld_value_cvitjs_config)) {
        $form_state->setErrorByName($fld_name_cvitjs_config, $this->t('The CViTjs configuration text looks incorrect. 
          It should contain a [general] section. See the help tab for more information.'));
      }
      
      if ($fld_value_cvitjs_config && !preg_match('/\[.*\]\s*\nconf = .*\ndefaultData =.*/m', $fld_value_cvitjs_config)) {    
        $form_state->setErrorByName($fld_name_cvitjs_config, $this->t('The CViTjs configuration text looks incorrect. 
          It should contain one section for each genome target. See the help tab for more information.'));
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

    // VISUALIZATION CONFIGURATIONS:
    $fld_value_blast_cvitjs_enabled = $form_state->getValue('fld_checkbox_blast_cvitjs_enabled');
    $fld_value_blast_cvitjs_config = $form_state->getValue('fld_text_blast_cvitjs_config');

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
      ->set('tripal_blast_config_visualization.cvitjs_enabled', $fld_value_blast_cvitjs_enabled)
      ->set('tripal_blast_config_visualization.cvitjs_config', $fld_value_blast_cvitjs_config)
      ->set('tripal_blast_config_notification.warning_text', $fld_value_blast_warning_text)      
      ->save();

    // Save configuration to file.
    if ($fld_value_blast_cvitjs_enabled && $fld_value_blast_cvitjs_config) {
      $cvit_conf_path = getcwd() . DIRECTORY_SEPARATOR;
       //@TODO . blast_ui_get_cvit_conf($form_state['values']['cvitjs_location']);
      
      if ($fh = fopen($cvit_conf_path, 'w')) {
        fwrite($fh, $fld_value_blast_cvitjs_config);
        fclose($fh);
      }
      else {
        $form_state->setError('fld_text_blast_cvitjs_config', 
          'Unable to open CViTjs conf file for writing: <pre>' . print_r(error_get_last(), true) . '</pre>');
      }
    }

    return parent::submitForm($form, $form_state);
  }
}