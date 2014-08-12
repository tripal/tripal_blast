<?php

/**
 * @file
 * Administrative interface for the BLAST UI
 */

/**
 *
 */
function blast_ui_admin_form($form, $form_state) {

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
 *
 */
function blast_ui_admin_form_submit($form, $form_state) {

  variable_set('blast_ui_nucleotide_example_sequence', $form_state['values']['nucleotide_example']);
  variable_set('blast_ui_protein_example_sequence', $form_state['values']['protein_example']);
}