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

  $form['general']['target_upload'] = array(
    '#type' => 'checkbox',
    '#title' => 'Enable Taget Sequence Upload',
    '#default_value' => FALSE,
    '#description' => 'When this option is checked, a file upload field will '
      . 'show up on the various BLAST forms under the Database section. This '
      . 'will allow users to upload a FASTA file they want to BLAST against '
      . '(database) as well as a FASTA file containing their query, providing '
      . 'the ultimate in flexibility for your users. However, since this does '
      . 'allow & in fact encourage the upload of large files which are then '
      . 'processed into BLAST databases, this option is disabled by default.',
    '#default_value' => variable_get(
        'blast_ui_allow_target_upload',
        FALSE
      )
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
 * Form validator for the blastdb node
 */
function blast_ui_validate($form, &$form_state) {
	$blast_path = $form_state['values']['blast_path'];
	$blast_path .= 'blastn';
	if(!empty($blast_path)) {
		if(file_exists($blast_path) ) {
			variable_set('blast_path', $form_state['values']['blast_path']);
		}
		else {
			form_set_error('blast_path', t('Please enter a valid path or you can leave it blank'));
		}
	}

}

/**
 *
 */
function blast_ui_admin_form_submit($form, $form_state) {

  variable_set('blast_ui_allow_target_upload', $form_state['values']['target_upload']);

  variable_set('blast_ui_nucleotide_example_sequence', $form_state['values']['nucleotide_example']);
  variable_set('blast_ui_protein_example_sequence', $form_state['values']['protein_example']);
}