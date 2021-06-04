<?php
/**
 * @file 
 * This is the controller for Tripal BLAST Configuration form. 
 */

namespace Drupal\tripal_blast\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;

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
  
      $form['general']['blast_path'] = [  
        '#type' => 'textfield',
        '#title' => t('Enter the path of the BLAST program'),
        '#description' => t('You can ignore if your $PATH variable is set. 
          Otherwise, enter the absoulte path to bin folder. For example, /opt/blast/2.2.29+/bin/'),
        '#default_value' => $config->get('tripal_blast_config_general.path')
      ];

      $form['general']['blast_threads'] = [
        '#type' => 'textfield',
        '#title' => t('Enter the number of CPU threads to use in blast search.'),
        '#description' => t('You can increase the number to reduce the search time. 
          Before you increase, please check your hardware configurations. 
          A value of one(1) can result in a slower search for some programs eg. tblastn.'),
        '#default_value' => $config->get('tripal_blast_config_general.threads')
      ];
    
      $form['general']['eVal'] = [
        '#type' => 'textfield',
        '#title' => t('Default e-value (Expected Threshold)'),
        '#description' => t('Expected number of chance matches in a random model. 
          This number should be give in a decimal format.'),
        '#default_value' => $config->get('tripal_blast_config_general.eval')
      ];
    
      $form['general']['qRange'] = [
        '#type' => 'textfield',
        '#title' => t('Default max matches in a query range'),
        '#description' => t('Limit the number of matches to a query range. 
          This option is useful if many strong matches to one part of a query may prevent 
          BLAST from presenting weaker matches to another part of the query.'),
        '#default_value' => $config->get('tripal_blast_config_general.qrange')
      ];

    
    //
    // # FILE UPLOAD CONFIGURATIONS:  
    $form['file_upload'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => t('Allow File Upload'),
      '#description' => t('The following options allow you to control whether your users can
        upload files for the query or target respectively. The ability to upload files allows
        them to more conviently BLAST large sets of sequences. However, the size of the
        files could be problematic, storage-wise, on your server.<br />')
    ];

      $form['file_upload']['query_upload'] = [
        '#type' => 'checkbox',
        '#title' => t('Enable Query Sequence Upload'),
        '#description' => t('When checked, a query file upload field will be available on BLAST request forms.'),
        '#default_value' => $config->get('tripal_blast_config_upload.allow_query')
      ];
    
      $form['file_upload']['target_upload'] = [
        '#type' => 'checkbox',
        '#title' => 'Enable Target Sequence Upload',
        '#description' => 'When checked, a target file upload field will be available on BLAST request forms.',
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

      $form['example_sequence']['nucleotide_example'] = [
        '#type' => 'textarea',
        '#title' => t('Nucleotide Example'),
        '#description' => t('Enter a complete nucleotide FASTA record including the header.'),
        '#default_value' => $config->get('tripal_blast_config_sequence.nucleotide')
      ];

      $form['example_sequence']['protein_example'] = [
        '#type' => 'textarea',
        '#title' => 'Protein Example',
        '#description' => t('Enter a complete protein FASTA record including the header.'),
        '#default_value' => $config->get('tripal_blast_config_sequence.protein')
      ];

    
    //
    // # JOBS CONFIGURATIONS:  
    $form['protection'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => t('Protect against large jobs'),
      '#description' => t('Depending on the size and nature of your target databases, 
        you may wish to constrain use of this module.'),
    ];

      $form['protection']['max_results_displayed'] = [
        '#type' => 'textfield',
        '#title' => t('Maximum number of results to show on report page'),
        '#description' => 'If there are more hits that this, the user is 
          able to download but not visualize the results.',
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
      '#title' => t('Enable and configure genome visualization'),
      '#description' => t('The JavaScript program CViTjs enables users to see BLAST hits on an
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

      $form['visualization']['cvitjs_enabled'] = [
        '#type' => 'checkbox',
        '#title' => t('Enable CViTjs'),
        '#description' => t('When checked, CViTjs will be enabled.'),
        '#default_value' => $config_cvitjs,
      ];
    
      // Get CViTjs confuration text, if possible.
      // @TODO blast_ui_get_cvit_conf_text() goes in '' the condition below.
      if (!$default_value = '') {
        $default_value = 'Unable to get CViTjs configuration information. You will need to enable CViTjs and set and save the path to CViTjs before you can edit the CViTjs configuration text.';

        $disabled = true;
      }
      else {
        $disabled = false;
      }

      $description = t('This is the contents of the file that defines data directories and 
        backbone GFF files for each genome assembly target. It is named
        cvit.conf and is in the root directory for the CViTjs javascript code.
        This is NOT the config file that is used to build the display for each
        individual genome. See the help tab for more information about
        configuration files.');

      $form['visualization']['cvitjs_config'] = [
        '#type' => 'textarea',
        '#title' => t('CViTjs configuration'),
        '#description' => $description,
        '#default_value' => $default_value,
        '#rows' => 10,
        '#disabled' => $disabled,
      ];

    return parent::buildForm($form, $form_state);
  }
  /**
   * {@inheritdoc}
   * Save configuration.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    return parent::submitForm($form, $form_state);
  }
}