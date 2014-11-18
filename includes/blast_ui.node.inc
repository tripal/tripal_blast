<?php

/**
 * @file
 * Contains all functions for creating the blastdb node type
 */


/**
 * Implements hook_node_info().
 */
function blast_ui_node_info() {
  return array(
    'blastdb' => array(
      'name' => t('Blast Database'),
      'base' => 'blastdb',
      'description' => t('Registers a BLAST Database for use with the BLAST UI.'),
    ),
  );
}

/**
 * Implements hook_node_access().
 */
function blastdb_node_access($node, $op, $account) {
  $node_type = $node;
  if (is_object($node)) {
    $node_type = $node->type;
  }

  if($node_type == 'blastdb') {
    if ($op == 'create') {
      if (!user_access('create Blast Database', $account)) {
        return NODE_ACCESS_DENY;
      }
      return NODE_ACCESS_ALLOW;
    }
    if ($op == 'update') {
      if (!user_access('edit Blast Database', $account)) {
        return NODE_ACCESS_DENY;
      }
    }
    if ($op == 'delete') {
      if (!user_access('delete Blast Database', $account)) {
        return NODE_ACCESS_DENY;
      }
    }
    if ($op == 'view') {
      if (!user_access('access Blast Database', $account)) {
        return NODE_ACCESS_DENY;
      }
    }

    return NODE_ACCESS_IGNORE;
  }
}

/**
 * Form constructor for the blastdb node
 *
 * @see blastdb_insert()
 * @see blastdb_update()
 * @see blastdb_delete()
 * @see blastdb_load()
 */
function blastdb_form($node, &$form_state) {
  $form = array();

  $form['#validate'] = array('blastdb_form_validate');

  $form['core'] = array(
    '#type' => 'fieldset',
    '#title' => 'General'
  );

  $form['core']['db_name']= array(
    '#type' => 'textfield',
    '#title' => t('Human-readable Name for Blast database'),
    '#required' => TRUE,
    '#default_value' => isset($node->db_name) ? $node->db_name : '',
  );

  $form['core']['db_path']= array(
    '#type' => 'textfield',
    '#title' => t('File Prefix including Full Path'),
    '#description' => t('The full path to your blast database including the file name but not the file type suffix. For example, /var/www/website/sites/default/files/myblastdb'),
    '#required' => TRUE,
    '#default_value' => isset($node->db_path) ? $node->db_path : '',
  );

  $form['core']['db_dbtype'] = array(
    '#type' => 'radios',
    '#title' => t('Type of the blast database'),
    '#options' => array(
      'nucleotide' => t('Nucleotide'),
      'protein' => t('Protein'),
    ),
    '#required' => TRUE,
    '#default_value' => isset($node->db_dbtype) ? $node->db_dbtype : 'n',
  );

  $form['dbxref'] = array(
    '#type' => 'fieldset',
    '#title' => 'Database References',
    '#description' => 'These settings can be used to tell the BLAST UI that '
      . 'information about the records in this BLAST database can be found '
      . 'either in the current website or an external website.'
  );

  $description = array(
    'default' => 'A single word followed by a free-text definition. '
      . 'The first word contains only alphanumeric characters and optionally '
      . 'underscores and will be used as the ID of the sequence.',
    'genbank' => 'Follows the same format as the first option '
      . 'except that the first "word" is of the following form: '
      . 'gb|accession|locus. The accession will be used as the ID of the sequence.',
    'embl' => 'Follows the same format as the first option '
      . 'except that the first "word" is of the following form: '
      . 'emb|accession|locus. The accession will be used as the ID of the sequence.',
    'swissprot' => 'Follows the same format as the first option '
      . 'except that the first "word" is of the following form: '
      . 'sp|accession|entry name. The accession will be used as the ID of the sequence.',
    'custom' => 'Allows you to use a regular expression (define one below) to '
      . 'extract a specifc portion of the FASTA header to be used as the ID.'
  );
  $form['dbxref']['dbxref_id_type'] = array(
    '#type' => 'radios',
    '#title' => 'FASTA header format',
    '#description' => 'Choose the format that matches the format of the FASTA '
      . 'headers in this BLAST database or choose custom to define your own '
      . 'using regular expressions. This ID will be appended to the URL Prefix '
      . ' of the database selected below.',
    '#options' => array(
      'default' => '<span title="' . $description['default'] . '">Generic</span>',
      'genbank' => '<span title="' . $description['genbank'] . '">NCBI GenBank</span>',
      'embl' => '<span title="' . $description['embl'] . '">EMBL Data Library</span>',
      'swissprot' => '<span title="' . $description['swissprot'] . '">SWISS-PROT</span>',
      'custom' => '<span title="' . $description['custom'] . '">Custom Format</span>',
    ),
    '#default_value' => (isset($node->linkout->regex_type)) ? $node->linkout->regex_type : 'default',
    '#ajax' => array(
      'callback' => 'ajax_blast_ui_node_linkout_custom_callback',
      'wrapper' => 'link-regex',
    )
  );

  $hide_regex = TRUE;
  if (isset($form_state['values']['dbxref_id_type'])) {
    if ($form_state['values']['dbxref_id_type'] == 'custom') {
      $hide_regex = FALSE;
    }
  }
  $form['dbxref']['regex'] = array(
    '#type' => 'textfield',
    '#title' => 'Regular Expression',
    '#description' => t('A PHP Regular expression with curved brackets '
      . 'surrounding the ID that should be used in the URL to provide a link-'
      . 'out to additional information. See <a href="@url" target="_blank">PHP.net Regular '
      . 'Expression Documentation</a> for more information. <strong>Be sure '
      . 'to include the opening and closing slashes</strong>. This is only '
      . 'available if custom was choosen for the FASTA header format above.',
      array('@url' => 'http://php.net/manual/en/reference.pcre.pattern.syntax.php')),
    '#disabled' => $hide_regex,
    '#prefix' => '<div id="link-regex">',
    '#suffix' => '</div>',
    '#default_value' => (isset($node->linkout->regex)) ? $node->linkout->regex : ''
  );

  $db_options = tripal_get_db_select_options();
  $db_options[0] = '';
  asort($db_options);
  $form['dbxref']['db_id'] = array(
    '#type' => 'select',
    '#title' => 'External Database',
    '#description' => 'The external database you would like to link-out to. '
      . 'Note that this list includes all Tripal Databases and if the database '
      . 'you would like to link-out to is not included you can add it through '
      . l('Administration > Tripal > Chado Modules > Databases','admin/tripal/chado/tripal_db/add', array('attributes' => array('target' => '_blank'))) . '.',
    '#options' => $db_options,
    '#default_value' => (isset($node->linkout->db_id->db_id)) ? $node->linkout->db_id->db_id : 0
  );

  $types = module_invoke_all('blast_linkout_info');
  $options = array();
  foreach ($types as $machine_name => $details) {
    $options[$machine_name] = (isset($details['name'])) ? $details['name'] : $machine_name;
  }
  $form['dbxref']['dbxref_linkout_type'] = array(
    '#type' => 'select',
    '#title' => 'Link-out Type',
    '#description' => 'This determines how the URL to be linked to is formed. NOTE: this is very dependant on the External Database chosen above since it needs to be able to support the type of linking choosen. For example, only External Databases which reference a GBrowse instance can use the GBrowse link type.',
    '#options' => $options,
    '#default_value' => (isset($node->linkout->type)) ? $node->linkout->type : 'link'
  );

  return $form;
}

function blastdb_form_validate($form, $form_state) {

  if (!empty($form_state['values']['regex'])) {
    // Check that any supplied regex includes //.
    if (!preg_match('/\/.*\//', $form_state['values']['regex'])) {
      form_set_error('regex', 'Regular Expressions require opening and closing slashes to delinate them. For example, <em>/^(\s+) .*/</em>');
    }
    // Check that the supplied regex is valid.
    elseif (@preg_match($form_state['values']['regex'], NULL) === FALSE) {
      form_set_error('regex', 'Regular Expression not valid. See '
        . '<a href="http://php.net/manual/en/reference.pcre.pattern.syntax.php" target="_blank">PHP.net Regular '
        . 'Expression Documentation</a> for more information.');
    }
  }

  // Check that the supplied db actually contains a URL prefix.
  if ($form_state['values']['db_id']) {
    $db = tripal_get_db(array('db_id' => $form_state['values']['db_id']));
    if (empty($db)) {
      form_set_error('db_id', 'The database chosen no longer exists.');
    }
    if (empty($db->urlprefix)) {
      form_set_error('db_id', 'The database choosen does not have a URL prefix '
        . 'listed which means a link-out could not be created for BLAST hits. '
        . 'Please edit the database '
        . l('here', 'admin/tripal/chado/tripal_db/edit/' . $db->db_id,
            array('attributes' => array('target' => '_blank')))
        . ' to include a URL prefix before continuing'
      );
    }
  }
}

/**
 * Implements hook_insert().
 */
function blastdb_insert($node) {

  // Hangle Link-out Rules.
  if ($node->dbxref_id_type == 'custom') {
    $regex = $node->regex;
  }
  else {
    $regex = $node->dbxref_id_type;
  }

  // Actually insert the record.
  db_insert('blastdb')->fields(array(
    'nid' => $node->nid,
    'name' => $node->db_name,
    'path' => $node->db_path,
    'dbtype' => $node->db_dbtype,
    'dbxref_id_regex' => $regex,
    'dbxref_db_id' => $node->db_id,
    'dbxref_linkout_type' => $node->dbxref_linkout_type
  ))->execute();

}

/**
 * Implements hook_node_insert().
 * This function acts on ALL NODES
 */
function blast_ui_node_insert($node) {
  if ($node->type == 'blastdb') {
    $node->title = $node->db_name;
  }
}

/**
 * Implements hook_update().
 */
function blastdb_update($node) {

  // Hangle Link-out Rules.
  if ($node->dbxref_id_type == 'custom') {
    $regex = $node->regex;
  }
  else {
    $regex = $node->dbxref_id_type;
  }

  // Actually insert the record.
  db_update('blastdb')->fields(array(
    'name' => $node->db_name,
    'path' => $node->db_path,
    'dbtype' => $node->db_dbtype,
    'dbxref_id_regex' => $regex,
    'dbxref_db_id' => $node->db_id,
    'dbxref_linkout_type' => $node->dbxref_linkout_type
  ))->condition('nid', $node->nid)->execute();
}

/**
 * Implements hook_node_update().
 * This	function acts on ALL NODES
 */
function blast_ui_node_update($node) {
  if ($node->type == 'blastdb')	{
    $node->title = $node->db_name;
  }
}

/**
 * Implements hook_delete().
 */
function blastdb_delete($node) {
   db_delete('blastdb')->condition('nid',$node->nid)->execute();
}

/**
 * Implements hook_load()   .
 */
function blastdb_load($nodes) {

  $result = db_query('SELECT nid, name, path, dbtype, dbxref_id_regex, dbxref_db_id, dbxref_linkout_type FROM {blastdb} WHERE nid IN (:nids)', array(':nids' => array_keys($nodes)));

  foreach ($result as $record) {
    $nodes[$record->nid]->db_name = $record->name;
    $nodes[$record->nid]->db_path = $record->path;
    $nodes[$record->nid]->title = $record->name;
    $nodes[$record->nid]->db_dbtype = $record->dbtype;

    if ($record->dbxref_id_regex) {
      $nodes[$record->nid]->linkout = new stdClass();

      if (preg_match('/\/.*\//', $record->dbxref_id_regex)) {
        $nodes[$record->nid]->linkout->regex_type = 'custom';
        $nodes[$record->nid]->linkout->regex = $record->dbxref_id_regex;
      }
      else {
        $nodes[$record->nid]->linkout->regex_type = $record->dbxref_id_regex;
        $nodes[$record->nid]->linkout->regex = get_blastdb_linkout_regex($nodes[$record->nid]);
      }
      $nodes[$record->nid]->linkout->db_id = tripal_get_db(array('db_id' => $record->dbxref_db_id));
      $nodes[$record->nid]->linkout->none = FALSE;

      // Support complex link-outs.
      $nodes[$record->nid]->linkout->type = $record->dbxref_linkout_type;
      $types = module_invoke_all('blast_linkout_info');
      if (isset($types[$record->dbxref_linkout_type])) {
        $nodes[$record->nid]->linkout->url_function = $types[$record->dbxref_linkout_type]['process function'];
      }
      else {
        $nodes[$record->nid]->linkout->url_function = '';
        tripal_report_error(
          'blast_ui',
          TRIPAL_ERROR,
          'Unable to find details on the type of link-out choosen (%type). Have you defined hook_blast_linkout_info()? Make sure to clear the cache once you do so.',
          array('%type' => $record->dbxref_linkout_type)
        );
      }
    }
    else {
      $nodes[$record->nid]->linkout = new stdClass();
      $nodes[$record->nid]->linkout->regex = '';
      $nodes[$record->nid]->linkout->db_id = 0;
      $nodes[$record->nid]->linkout->none = TRUE;
    }
  }

}

/**
 * AJAX Callback: Update Node Link-out Regex Textfield.
 *
 * On BlastDB node form the Link-out (dbxref) options allow for settings of a
 * custom regex which should only be enabled when "Custom" is selected. This
 * callback refreshes the regex textfield so it can change (ie: become enabled)
 * when someone selects custom.
 */
function ajax_blast_ui_node_linkout_custom_callback($form, $form_state) {
  return $form['dbxref']['regex'];
}
