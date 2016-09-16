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
    '#title' => t('e-Value (Expected Threshold)'),
    '#description' => t('Expected number of chance matches in a random model. This number should be give in a decimal format.'),
     '#default_value' => variable_get('eVal', 0.001),
    //'#default_value' => variable_get('blast_threads', 1),
  );

  $form['general']['qRange']= array(
    '#type' => 'textfield',
    '#title' => t('Max matches in a query range'),
    '#description' => t('Limit the number of matches to a query range. This option is useful if many strong matches to one part of a query may prevent BLAST from presenting weaker matches to another part of the query.'),
    '#default_value' => variable_get('qRange', 0),
  );

  $form['file_upload'] = array(
    '#type' => 'fieldset',
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
      array('@fasta-format-url' => 'http://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml')),
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
      array('@fasta-format-url' => 'http://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml')),
    '#default_value' => variable_get(
        'blast_ui_protein_example_sequence',
        $protein_default
      )
  );

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
  $blast_path = $form_state['values']['blast_path'];
  $blast_path .= 'blastn';
  if(!empty($form_state['values']['blast_path'])) {
    if(file_exists($blast_path) ) {
      variable_set('blast_path', $form_state['values']['blast_path']);
    }
    else {
      form_set_error('blast_path', t('Please enter a valid path not including the name of the blast program (ie: /urs/bin/). You can leave this blank if you have your $PATH variable set appropriately.'));
    }
  }

}

/**
 * Submit the Admin/settings form.
 */
function blast_ui_admin_form_submit($form, $form_state) {

  variable_set('blast_path', $form_state['values']['blast_path']);
  variable_set('blast_threads', $form_state['values']['blast_threads']);

  variable_set('eVal', $form_state['values']['eVal']);
  variable_set('qRange', $form_state['values']['qRange']);

  variable_set('blast_ui_allow_query_upload', $form_state['values']['query_upload']);
  variable_set('blast_ui_allow_target_upload', $form_state['values']['target_upload']);

  variable_set('blast_ui_nucleotide_example_sequence', $form_state['values']['nucleotide_example']);
  variable_set('blast_ui_protein_example_sequence', $form_state['values']['protein_example']);
}
