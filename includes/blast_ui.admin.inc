<?php

/**
 * @file
 * Administrative interface for the BLAST UI
 */

/**
 *
 */
function blast_ui_admin_form($form, $form_state) {

  $form['general'] = array(
    '#type' => 'fieldset',
    '#title' => 'General'
  );

  $form['general']['blast_path']= array(
    '#type' => 'textfield',
    '#title' => t('Enter the path of the BLAST program'),
    '#description' => t('You can ignore if your $PATH variable is set. Otherwise, enter the absoulte path to bin folder. For example, /opt/blast/2.2.29+/bin/'),
    '#default_value' => variable_get('blast_path', ''),
  );

  $form['general']['blast_threads']= array(
    '#type' => 'textfield',
    '#title' => t('Enter the number of CPU threads to use in blast search.'),
    '#description' => t('You can increase the number to reduce the search time. Before you increase, please check your hardware configurations . A value of one(1) can result in a slower search for some programs eg. tblastn.'),
    '#default_value' => variable_get('blast_threads', 1),
  );

   $form['general']['eVal']= array(
    '#type' => 'textfield',
    '#title' => t('Default e-value (Expected Threshold)'),
    '#description' => t('Expected number of chance matches in a random model. This number should be give in a decimal format.'),
     '#default_value' => variable_get('eVal', 0.001),
    //'#default_value' => variable_get('blast_threads', 1),
  );

  $form['general']['qRange']= array(
    '#type' => 'textfield',
    '#title' => t('Default max matches in a query range'),
    '#description' => t('Limit the number of matches to a query range. This option is useful if many strong matches to one part of a query may prevent BLAST from presenting weaker matches to another part of the query.'),
    '#default_value' => variable_get('qRange', 0),
  );

  $form['file_upload'] = array(
    '#type' => 'fieldset',
    '#collapsible' => true,
    '#collapsed' => true,
    '#title' => 'Allow File Upload',
    '#description' => 'The following options allow you to control whether your users can
      upload files for the query or target respectively. The ability to upload files allows
      them to more conviently BLAST large sets of sequences. However, the size of the
      files could be problematic, storage-wise, on your server.<br />'
  );

  $form['file_upload']['query_upload'] = array(
    '#type' => 'checkbox',
    '#title' => 'Enable Query Sequence Upload',
    '#description' => 'When checked, a query file upload field will be available on BLAST request forms.',
    '#default_value' => FALSE,
    '#default_value' => variable_get('blast_ui_allow_query_upload', TRUE)
  );

  $form['file_upload']['target_upload'] = array(
    '#type' => 'checkbox',
    '#title' => 'Enable Target Sequence Upload',
    '#description' => 'When checked, a target file upload field will be available on BLAST request forms.',
    '#default_value' => FALSE,
    '#default_value' => variable_get('blast_ui_allow_target_upload', FALSE)
  );

  $form['example_sequence'] = array(
    '#type' => 'fieldset',
    '#collapsible' => true,
    '#collapsed' => true,
    '#title' => 'Set Example Sequences',
    '#description' => 'There is the ability to show example sequences built-in to the various BLAST forms. Use the following fields to set these example sequences. This allows you to provide more relevant examples to your users.'
  );

  $nucleotide_default = '>partial lipoxygenase Glyma15g03040
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
  $form['example_sequence']['nucleotide_example'] = array(
    '#type' => 'textarea',
    '#title' => 'Nucleotide Example',
    '#description' => t('Enter a complete nucleotide FASTA record including the header. More information: <a href="@fasta-format-url" target="_blank">FASTA format</a>.',
      array('@fasta-format-url' => 'https://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml')),
    '#default_value' => variable_get(
        'blast_ui_nucleotide_example_sequence',
        $nucleotide_default
      )
  );

  $protein_default = '>gi|166477|gb|AAA96434.1| resveratrol synthase [Arachis hypogaea]
MVSVSGIRKVQRAEGPATVLAIGTANPPNCIDQSTYADYYFRVTNSEHMTDLKKKFQRICERTQIKNRHM
YLTEEILKENPNMCAYKAPSLDAREDMMIREVPRVGKEAATKAIKEWGQPMSKITHLIFCTTSGVALPGV
DYELIVLLGLDPCVKRYMMYHQGCFAGGTVLRLAKDLAENNKDARVLIVCSENTAVTFRGPSETDMDSLV
GQALFADGAAAIIIGSDPVPEVEKPIFELVSTDQKLVPGSHGAIGGLLREVGLTFYLNKSVPDIISQNIN
DALNKAFDPLGISDYNSIFWIAHPGGRAILDQVEQKVNLKPEKMKATRDVLSNYGNMSSACVFFIMDLMR
KRSLEEGLKTTGEGLDWGVLFGFGPGLTIETVVLRSVAI';
  $form['example_sequence']['protein_example'] = array(
    '#type' => 'textarea',
    '#title' => 'Protein Example',
    '#description' => t('Enter a complete protein FASTA record including the header. More information: <a href="@fasta-format-url" target="_blank">FASTA format</a>.',
      array('@fasta-format-url' => 'https://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml')),
    '#default_value' => variable_get(
        'blast_ui_protein_example_sequence',
        $protein_default
      )
  );

  // PROTECTION
  $form['protection'] = array(
    '#type' => 'fieldset',
    '#collapsible' => true,
    '#collapsed' => true,
    '#title' => 'Protect against large jobs',
    '#description' => 'Depending on the size and nature of your target databases, you may wish to constrain use of this module.',
  );

  $form['protection']['max_results_displayed'] = array(
    '#type' => 'textfield',
    '#title' => 'Maximum number of results to show on report page',
    '#description' => 'If there are more hits that this, the user is able to download but not visualize the results.',
    '#default_value' => variable_get('blast_ui_max_results_displayed', 500)
  );

  // CVITJS
  $cvitjs_enabled = variable_get('blast_ui_cvitjs_enabled', FALSE);
  $description = 'The JavaScript program CViTjs enables users to see BLAST hits on an '
               . 'entire genome assembly. See the help tab for information on how to '
               . 'download and set up CViTjs.';

  $form['cvitjs'] = array(
    '#type' => 'fieldset',
    '#collapsible' => true,
    '#collapsed' => !$cvitjs_enabled,
    '#title' => 'Enable and configure genome visualization',
    '#description' => $description,
  );

  $absolute_cvitjs_data_path = DRUPAL_ROOT . '/sites/all/libraries/cvitjs/data';
  $description = '<div class ="messages warning">CViTjs is only applicable for genome BLAST targets. After it is '
               . 'enabled here, CViTjs will need to be enabled for each applicable BLAST '
              . 'target node.</div>'
              . '<div class="messages status"><strong>CViTjs Data Location: '.$absolute_cvitjs_data_path.'</strong>'
              . '<br />The GFF3 and Genome Target-specific CViTjs configuration files should be located '
              . 'at the above system path. Feel free to organize this directory further. '
              . 'See the "Help" tab for more information.</div>';
  $form['cvitjs']['explanation'] = array(
    '#markup' => t($description),
  );


  $form['cvitjs']['cvitjs_enabled'] = array(
    '#type' => 'checkbox',
    '#title' => 'Enable CViTjs',
    '#description' => 'When checked, CViTjs will be enabled.',
    '#default_value' => $cvitjs_enabled,
  );

  // Get CViTjs confuration text, if possible.
  if (!$default_value = blast_ui_get_cvit_conf_text()) {
    $default_value = 'Unable to get CViTjs configuration information. '
                   . 'You will need to enable CViTjs and set and save the '
                   . 'path to CViTjs before you can edit the CViTjs configuration text.';
    $disabled = true;
  }
  else {
    $disabled = false;
  }

  $description = 'This is the contents of the file that defines data directories and '
               . 'backbone GFF files for each genome assembly target. It is named '
               . 'cvit.conf and is in the root directory for the CViTjs javascript code. '
               . 'This is NOT the config file that is used to build the display for each '
               . 'individual genome. See the help tab for more information about '
               . 'configuration files.';
  $form['cvitjs']['cvitjs_config'] = array(
    '#type' => 'textarea',
    '#title' => 'CViTjs configuration',
    '#description' => $description,
    '#default_value' => $default_value,
    '#rows' => 10,
    '#disabled' => $disabled,
  );

//eksc:
  // WARNING
  $description = 'This permits display of a temporary warning message at the top of the
                  BLAST input form. Text can include HTML tags. Remember to remove the
                  message when it is no longer relevant.';
  $form['warning'] = array(
    '#type' => 'fieldset',
    '#collapsible' => true,
    '#collapsed' => true,
    '#title' => 'Show warning text',
    '#description' => $description,
  );
    $form['warning']['warning_text'] = array(
    '#type' => 'textarea',
    '#title' => 'Text to be displayed',
    '#description' => $description,
    '#default_value' => variable_get('blast_ui_warning_text', ''),
    '#rows' => 10,
  );


  // SUBMIT
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Save Configuration'
  );

  return $form;
}

/**
 * Validate the Admin/Settings form.
 */
function blast_ui_admin_form_validate($form, &$form_state) {
  // Check path to BLAST executables
  $blast_path = $form_state['values']['blast_path'];
  $blast_path .= 'blastn';
  if(!empty($form_state['values']['blast_path'])) {
    if(file_exists($blast_path) ) {
      variable_set('blast_path', $form_state['values']['blast_path']);
    }
    else {
      form_set_error('blast_path', t('Please enter a valid path not including the name of the blast program (ie: /usr/bin/). You can leave this blank if you have your $PATH variable set appropriately.'));
    }
  }

  // Check path to CViTjs executable and make sure cvit.conf is writable
  if ($form_state['values']['cvitjs_enabled']) {
    $cvit_path = blast_ui_get_cvit_conf();
    if (!$cvit_path || !file_exists($cvit_path)) {
      $msg = "The CViTjs configuration file, cvit.conf, does not exist at the path given ("
           . $form_state['values']['cvitjs_location']
           . "). Please check your path. "
           . "If you have not yet downloaded CViTjs, see the help tab for more information.";
      form_set_error('cvitjs_location', t($msg));
    }

    if (!is_writable($cvit_path)) {
      $msg = "The file $cvit_path is not writable by this page. "
           . "Please enable write access for apache then try saving these settings again.";
      form_set_error('cvitjs_location', t($msg));
    }
  }

  // Empty contents of cvitjs_config textarea if it is disabled
  if ($form['cvitjs']['cvitjs_config']['#disabled']) {
    $form_state['values']['cvitjs_config'] = '';
  }

  // Check CViTjs configuration text
  if ($form_state['values']['cvitjs_config']
        && !preg_match('/\[general\]\s*\ndata_default =.*/m',
                       $form_state['values']['cvitjs_config'])) {
    $msg = "The CViTjs configuration text looks incorrect. "
         . "It should contain a [general] section. "
         . "See the help tab for more information.";
    form_set_error('cvitjs_config', t($msg));
  }
  if ($form_state['values']['cvitjs_config']
        && !preg_match('/\[.*\]\s*\nconf = .*\ndefaultData =.*/m',
                       $form_state['values']['cvitjs_config'])) {
    $msg = "The CViTjs configuration text looks incorrect. "
         . "It should contain one section for each genome target. "
         . "See the help tab for more information.";
    form_set_error('cvitjs_config', t($msg));
  }
}

/**
 * Submit the Admin/settings form.
 */
function blast_ui_admin_form_submit($form, $form_state) {
  //General
  variable_set('blast_path', $form_state['values']['blast_path']);
  variable_set('blast_threads', $form_state['values']['blast_threads']);

  variable_set('eVal', $form_state['values']['eVal']);
  variable_set('qRange', $form_state['values']['qRange']);

  // Uploads
  variable_set('blast_ui_allow_query_upload', $form_state['values']['query_upload']);
  variable_set('blast_ui_allow_target_upload', $form_state['values']['target_upload']);

  // Example sequence
  variable_set('blast_ui_nucleotide_example_sequence', $form_state['values']['nucleotide_example']);
  variable_set('blast_ui_protein_example_sequence', $form_state['values']['protein_example']);

  // Protect against large result sets
  variable_set('blast_ui_max_results_displayed', $form_state['values']['max_results_displayed']);

  // Whole genome visualization - CViTjs
  variable_set('blast_ui_cvitjs_enabled', $form_state['values']['cvitjs_enabled']);
  if ($form_state['values']['cvitjs_enabled'] && $form_state['values']['cvitjs_config']) {
    // Need absolute path to conf file to write
    $cvit_conf_path = getcwd() . DIRECTORY_SEPARATOR
                    . blast_ui_get_cvit_conf($form_state['values']['cvitjs_location']);
    if ($fh = fopen($cvit_conf_path, 'w')) {
      fwrite($fh, $form_state['values']['cvitjs_config']);
      fclose($fh);
    }
    else {
      drupal_set_message("Unable to open CViTjs conf file for writing: <pre>" . print_r(error_get_last(),true) . "</pre>");
    }
  }

//eksc:
  // Warning text
  variable_set('blast_ui_warning_text', $form_state['values']['warning_text']);
}
