<?php

/**
 * @file
 * Contains more generally applicable functions as well as some meant to help developers
 * Plug-in to the BLAST UI functionality
 */

/**
 * Get a specific BlastDB.
 *
 * @param $identifiers
 *   An array of identifiers used to determine which BLAST DB to retrieve.
 *
 * @return
 *   A fully-loaded BLAST DB Node
 */
function get_blast_database($identifiers) {
  $node = FALSE;

  if (isset($identifiers['nid'])) {
    $node = node_load($identifiers['nid']);
  }
  elseif (isset($identifiers['name'])) {

    $nid = db_query('SELECT nid FROM {blastdb} WHERE name=:name', array(':name' => $identifiers['name']))->fetchField();
    $node = node_load($nid);

  } elseif (isset($identifiers['path'])) {

    $nid = db_query('SELECT nid FROM {blastdb} WHERE path=:path', array(':path' => $identifiers['path']))->fetchField();
    $node = node_load($nid);

  }

  return $node;
}

/**
 * Returns a list BLAST DATABASE options
 *
 * @param $type
 *   The type of BLAST dabases to restrict the list to (ie: n: nucleotide or p: protein)
 *
 * @return
 *   An array where the nid is the key and the value is the human-readable name of the option
 */
function get_blast_database_options($type) {
  global $user;

  // Use the Entity API to get a list of BLAST Nodes to load
  // We use this function in order respect node access control so that
  // administrators can use this module in combination with a node access module
  // of their choice to limit access to specific BLAST databases.
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'node')
    // Restrict to BLASTDB nodes.
    ->entityCondition('bundle', 'blastdb')
    // Restrict to Published nodes.
    ->propertyCondition('status', 1)
    // Restrict to nodes the current user has permission to view.
    ->addTag('node_access');
  $entities = $query->execute();


  // Get all BlastDB nodes
  $nodes  = node_load_multiple(array_keys($entities['node']));

  // Support obsolete database type n/p
  $obs_type = '';
  if ($type == 'protein') {
    $obs_type = 'p';
  }
  else {
    $obs_type = 'n';
  }

  $options = array();
  foreach ($nodes as $node) {
    if ( isset($node) && isset($node->db_dbtype) ) {
	    if ( ($node->db_dbtype == $type) OR ($node->db_dbtype == $obs_type) ) {
        $options[$node->nid] = $node->db_name;
	    }
    }
  }

  asort($options);
  $options[0] = 'Select a Dataset';

  return $options;
}

/**
 * Run BLAST (should be called from the command-line)
 *
 * @param $program
 *   Which BLAST program to run (ie: 'blastn', 'tblastn', tblastx', 'blastp','blastx')
 * @param $query
 *   The full path and filename of the query FASTA file
 * @param $database
 *   The full path and filename prefix (excluding .nhr, .nin, .nsq, etc.)
 * @param $output_filestub
 *   The filename (not including path) to give the results. Should not include file type suffix
 * @param $options
 *   An array of additional option where the key is the name of the option used by
 *   BLAST (ie: 'num_alignments') and the value is relates to this particular
 *   BLAST job (ie: 250)
 */
function run_BLAST_tripal_job($program, $query, $database, $output_filestub, $options, $job_id = NULL) {

 $output_file = file_directory_temp() .  DIRECTORY_SEPARATOR . $output_filestub . '.blast.asn';
 $output_file_xml = variable_get('file_public_path', conf_path() . '/files') . DIRECTORY_SEPARATOR . $output_filestub . '.blast.xml';
 $output_file_tsv = variable_get('file_public_path', conf_path() . '/files') . DIRECTORY_SEPARATOR . $output_filestub . '.blast.tsv';
 $output_file_html = variable_get('file_public_path', conf_path() . '/files') . DIRECTORY_SEPARATOR . $output_filestub . '.blast.html';


  print "\nExecuting $program\n\n";
  print "Query: $query\n";
  print "Database: $database\n";
  print "Results File: $output_file\n";

  print "Options:\n";

  // Allow administrators to use an absolute path for these commands.
  // Defaults to using $PATH.
	$blast_path = variable_get('blast_path', '');
	$program = 	$blast_path . $program;
	$blast_formatter_command = $blast_path .  'blast_formatter';

  $blast_cmd = "$program -query '$query' -db '$database' -out '$output_file' -outfmt=11";
  if (!empty($options)) {
    foreach ($options as $opt => $val) {
      print "\t$opt: $val\n";
      $blast_cmd .= " -$opt $val";
    }
  }

  print "\nExecuting the following BLAST command:\n" . $blast_cmd . "\n";

  system($blast_cmd);

  if(!file_exists($output_file)) {
    tripal_report_error(
      'blast_ui',
      TRIPAL_ERROR,
      "BLAST did not complete successfully as is implied by the lack of output file (%file). The command run was @command",
      array('%file' => $output_file, '@command' => $blast_cmd),
      array('print' => TRUE)
    );
    return FALSE;
  }

  print "\nGenerating additional download formats...\n";

  print "\tXML\n";
  system("$blast_formatter_command -archive $output_file -outfmt 5 -out $output_file_xml");
  if(!file_exists($output_file_xml)) {
    tripal_report_error(
      'blast_ui',
      TRIPAL_ERROR,
      "Unable to convert BLAST ASN.1 archive (%archive) to XML (%file).",
      array('%archive' => $output_file, '%file' => $output_file_xml),
      array('print' => TRUE)
    );
  }

  print "\tTab-delimited\n";
  system("$blast_formatter_command -archive $output_file -outfmt 7 -out $output_file_tsv");
  if(!file_exists($output_file_tsv)) {
    tripal_report_error(
      'blast_ui',
      TRIPAL_WARNING,
      "Unable to convert BLAST ASN.1 archive (%archive) to Tabular Output (%file).",
      array('%archive' => $output_file, '%file' => $output_file_tsv),
      array('print' => TRUE)
    );
  }

  print "\tHTML (includes alignments)\n";
  system("$blast_formatter_command -archive $output_file -outfmt 0 -out $output_file_html -html");
  if(!file_exists($output_file_tsv)) {
    tripal_report_error(
      'blast_ui',
      TRIPAL_WARNING,
      "Unable to convert BLAST ASN.1 archive (%archive) to HTML Output (%file).",
      array('%archive' => $output_file, '%file' => $output_file_html),
      array('print' => TRUE)
    );
  }


  print "\nDone!\n";
}

/**
 * FASTA validating parser
 *
 * A sequence in FASTA format begins with a single-line description, followed
 * by lines of sequence data.The description line is distinguished from the
 * sequence data by a greater-than (">") symbol in the first column. The word
 * following the ">" symbol is the identifier of the sequence, and the rest of
 * the line is the description (both are optional). There should be no space
 * between the ">" and the first letter of the identifier. The sequence ends
 * if another line starting with a ">" appears which indicates the start of
 * another sequence.
 *
 * @param $type
 *   The type of sequence to be validated (ie: either nucleotide or protein).
 * @param $sequence
 *  A string of characters to be validated.
 *
 * @return
 *  Return a boolean. 1 if the sequence does not pass the format valifation stage and 0 otherwise.
 *
 */
function validate_fasta_sequence($type, $sequence) {

  if ($type == 'nucleotide') {
    $fastaIdRegEx = '/^>.*(\\n|\\r)/';
    $fastaSeqRegEx = '/[^acgntuACGNTU\n\r]/';
    if ( preg_match($fastaSeqRegEx,$sequence) && !(preg_match($fastaIdRegEx,$sequence)) ) {
      return TRUE;
    } else {
      return FALSE;
    }
  } elseif ($type == 'protein') {
    $fastaIdRegEx = '/^>.*(\\n|\\r)/';
    $fastaSeqRegEx = '/[^acgturykmswbdhvnxACGTURYKMSWBDHVNX\*\-\n\r]/';
    if ( preg_match($fastaSeqRegEx,$sequence) && !(preg_match($fastaIdRegEx,$sequence)) ) {
      return TRUE;
    } else {
      return FALSE;
    }
  }
  return FALSE;
}

/**
 * Retrieve the regex to capture the Link-out Accession from the Hit Def.
 *
 * @param $nid
 *   The node ID of the BLAST database the hit is from.
 * @param $options
 *   An array of options that can be passed to this function. Supported
 *   options include:
 *    -
 *
 * @return
 *   A PHP regex for use with preg_match to cature the Link-out Accession.
 */
function get_blastdb_linkout_regex($node, $options = array()) {

  if (empty($node->linkout->regex)) {
    switch ($node->linkout->regex_type) {
      case 'default':
        $regex = '/^(\S+).*/';
        break;
      case 'genbank':
        $regex = '/^gb\|([^\|])*\|.*/';
        break;
      case 'embl':
        $regex = '/^embl\|([^\|])*\|.*/';
        break;
      case 'swissprot':
        $regex = '/^sp\|([^\|])*\|.*/';
        break;
    }
  }
  else {
    $regex = $node->linkout->regex;
  }

  return $regex;
}
