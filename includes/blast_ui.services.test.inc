<?php
/**
 * @file
 * Contains an interface for testing your own blast web services.
 */

/**
 * Form: Test Webservices.
 */
function blast_ui_test_ws_form($form, $form_state) {

  $form['msg'] = array(
    '#type' => 'markup',
    '#markup' => '<p>You can use this form to perform a blast query against your own
      web services as a means of testing them.</p>
      <h3>Assumptions:</h3>
      <ul>
      <li>Your site uses HTTP.</li>
      <li>Tests BLASTn</li>
      </ul>',
  );

  $db_type = 'nucleotide';

  $default_query = ">partial lipoxygenase Glyma15g03040
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
CTGTTTTTAT TCAACATATT TAATCACATG GATGAATTTT TGAACTGTTA";

  $form['query'] = array(
    '#type' => 'textarea',
    '#title' => t('Enter FASTA sequence(s)'),
    '#description'=>t('Enter query sequence(s) in the text area.'),
    '#default_value' => $default_query,
    '#required' => TRUE,
  );

  $options = get_blast_database_options($db_type);
  $form['db'] = array(
    '#type' => 'select',
    '#title' => t('%type BLAST Databases:', array('%type' => ucfirst($db_type))),
    '#options' => $options,
    '#empty_option' => t('Select a Dataset'),
    '#required' => TRUE,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Query via Web Services',
  );

  return $form;
}

/**
 * Test Webservices: Validate handler.
 */
function blast_ui_test_ws_form_validate($form, &$form_state) {

}

/**
 * Test Webservices: Submit handler.
 */
function blast_ui_test_ws_form_submit($form, &$form_state) {
  global $user;
  global $base_url;

  $endpoint = $base_url . '/restapi/';

  $args = array(
    'endpoint' => $endpoint,
    'query' => $form_state['values']['query'],
    'db_nid' => $form_state['values']['db']
  );
  tripal_add_job(
    'Test BLAST Web Services',
    'blast_ui',
    'test_blast_webservices_job',
    array( $args ),
    $user->uid
  );

}

/**
 * Test Webservices: Tripal Job Callback.
 */
function test_blast_webservices_job($args, $job_id = NULL) {

  $endpoint = $args['endpoint'];
  $query = $args['query'];
  $db = $args['db_nid'];

  print "Web Services: " . $endpoint . "\n";

  // Get BLAST programs
  //-------------------------
  $service_url = $endpoint . "blast.json";

  print "\n" . str_repeat('-',30) . "\n";
  print "Service: List Blast Programs available.\n";
  print "URL: " . $service_url . "\n";

  $ch = curl_init($service_url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HEADER, false);
  curl_setopt($ch, CURLOPT_HTTPHEADER,array (
        "Accept: application/json",
        "Content-type: application/json"
        ));
  $response = json_decode(curl_exec($ch));

  print "Result: " . print_r($response, TRUE) . "\n";
  if (!empty($response)) { drush_log('Service request successful', 'ok'); }
  else { return drush_set_error('Service request failed.'); }

  // Get options for BLASTn
  //-------------------------
  $service_url = $endpoint . "blast/getDatabaseOptions.json";

  print "\n" . str_repeat('-',30) . "\n";
  print "Service: Retrieve options for blastn.\n";
  print "URL: " . $service_url . "\n";

  $post = array(
    "db_type" => 'nucleotide',
    "blast_program" => 'blastn'
  );
  print "POST: " . print_r($post, TRUE) . "\n";

  $ch = curl_init($service_url);
  $post = http_build_query($post, '', '&');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
  $response = json_decode(curl_exec($ch));

  print "Result: " . print_r($response, TRUE) . "\n";
  if (!empty($response)) { drush_log('Service request successful', 'ok'); }
  else { return drush_set_error('Service request failed.'); }

  // Make BLAST job request
  //-------------------------
  $service_url = $endpoint . "blast/makeJobRequest.json";

  print "\n" . str_repeat('-',30) . "\n";
  print "Service: Make BLAST job request.\n";
  print "URL: " . $service_url . "\n";


  $blast_db              = get_blast_database(array('nid' => $db));
  $database              = $blast_db->db_name;
  $max_target_sequences  = '50';
  $word_size             = '11';
  $match_mismatch_scores = '1,-3';
  $gap_costs             = 'Existence: 1 Extension: 2';
  $post = array("query_type"            => 'nucleotide',
                "db_type"               => 'nucleotide',
                "database"              => $database,
                "max_target_sequences"  => $max_target_sequences,
                "word_size"             => $word_size,
                "match_mismatch_scores" => $match_mismatch_scores,
                "gap_costs"             => $gap_costs,
                "query"                 => $query
              );
  print "POST: " . print_r($post, TRUE) . "\n";

  $ch = curl_init($service_url);
  $post = http_build_query($post, '', '&');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
  $response = json_decode(curl_exec($ch));

  print "Result: " . print_r($response, TRUE) . "\n";
  if (isset($response->job_id)) { drush_log('Service request successful', 'ok'); }
  else { return drush_set_error('Service request failed.'); }

  // Here is the job ID
  $job_id = $response->job_id;

  // Check job status
  //-------------------------
  $service_url = $endpoint . "blast/getBlastStatus.json";

  print "\n" . str_repeat('-',30) . "\n";
  print "Service: Check job status.\n";
  print "URL: " . $service_url . "\n";

  $ch = curl_init($service_url);
  $post = array("job_id" => $job_id);
  print "POST: " . print_r($post, TRUE) . "\n";

  $post = http_build_query($post, '', '&');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
  do {
    sleep(2);
    $response = json_decode(curl_exec($ch));
    print "Response: " . print_r($response, TRUE) . "\n";
  } while($response[0] == 'Running');

  if ($response[0] == 'Completed') { drush_log('Service request successful', 'ok'); }
  else { return drush_set_error('Service request failed.'); }

  // Get job results
  //-------------------------
  $service_url = $endpoint . "blast/$job_id.json";

  print "\n" . str_repeat('-',30) . "\n";
  print "Service: Retrieve Results.\n";
  print "URL: " . $service_url . "\n";

  $ch = curl_init($service_url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HEADER, false);
  curl_setopt($ch, CURLOPT_HTTPHEADER,array(
              "Accept: application/json",
              "Content-type: application/json"
  ));
  $response = json_decode(curl_exec($ch));
  print "Results: " . print_r($response, TRUE) . "\n";
  if (isset($response->status)) { return drush_set_error('Service request failed.'); }
  else { drush_log('Service request successful', 'ok'); }
}
