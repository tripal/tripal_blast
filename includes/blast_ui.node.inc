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

  $form['db_name']= array(
    '#type' => 'textfield',
    '#title' => t('Human-readable Name for Blast database'),
    '#required' => TRUE,
    '#default_value' => isset($node->db_name) ? $node->db_name : '',
  );

  $form['db_path']= array(
    '#type' => 'textfield',
    '#title' => t('File Prefix including Full Path'),
    '#description' => t('The full path to your blast database including the file name but not the file type suffix. For example, /var/www/website/sites/default/files/myblastdb'),
    '#required' => TRUE,
    '#default_value' => isset($node->db_path) ? $node->db_path : '',
  );

  $form['db_dbtype'] = array(
    '#type' => 'radios',
    '#title' => t('Type of the blast database'),
    '#options' => array(
      'n' => t('Nucleotide'),
      'p' => t('Protein'),
    ),
    '#required' => TRUE,
    '#default_value' => isset($node->db_dbtype) ? $node->db_dbtype : 'n',
  );

  return $form;
}

/**
 * Implements hook_insert().
 */
function blastdb_insert($node) {

  db_insert('blastdb')->fields(array(
    'nid' => $node->nid,
    'name' => $node->db_name,
    'path' => $node->db_path,
    'dbtype' => $node->db_dbtype,
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
    db_update('blastdb')->fields(array(
    'name' => $node->db_name,
    'path' => $node->db_path,
    'dbtype' => $node->db_dbtype,
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

  $result = db_query('SELECT nid, name, path, dbtype FROM {blastdb} WHERE nid IN (:nids)', array(':nids' => array_keys($nodes)));

  foreach ($result as $record) {
    $nodes[$record->nid]->db_name = $record->name;
    $nodes[$record->nid]->db_path = $record->path;
    $nodes[$record->nid]->title = $record->name;
    $nodes[$record->nid]->db_dbtype = $record->dbtype;
  }

}

