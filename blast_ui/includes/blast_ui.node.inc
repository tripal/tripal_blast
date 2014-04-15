<?php

/**
 * @file
 * Contains all functions for creating the blastdb node type
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

function blastdb_form($node, &$form_state) {
  $form = array();
  
  $form['db_name']= array(
    '#type' => 'textfield',
    '#title' => t('File Name'),
    '#required' => TRUE,
    '#default_value' => isset($node->db_name) ? $node->db_name : '',
  );
  $form['db_path']= array(
    '#type' => 'textfield',
    '#title' => t('File Address'),
    '#required' => TRUE,
    '#default_value' => isset($node->db_path) ? $node->db_path : '',
  );
//  $form['#attributes']['enctype'] = 'multipart/form-data';
//  $form['db_path'] = array(
//  	'#prefix' => 'Upload the file you wish to convert to a Blast db file',
//  	'#type' => 'file',
//  	'#description' => t('Please give a text or a FASTA file, not a MS-Word or other document, you can upload up to 10 Mb.'),
//  );

  

  return $form;
}

function blastdb_insert($node) {
 
  db_insert('blastdb')->fields(array(
    'nid' => $node->nid,
    'name' => $node->db_name,
    'path' => $node->db_path,
  ))->execute();
  
//  exec("makeblastdb -in $node->db_path -dbtype nucl -out $node->db_name");
  
}

function blastdb_update($node) {
    db_update('blastdb')->fields(array(
    'name' => $node->db_name,
    'path' => $node->db_path,
  ))->condition('nid', $node->nid)->execute();
}

function blastdb_delete($node) {
   db_delete('blastdb')->condition('nid',$node->nid)->execute();
}

function blastdb_load($nodes) {
    
  $result = db_query('SELECT nid, name, path FROM {blastdb} WHERE nid IN (:nids)', array(':nids' => array_keys($nodes)));
  
  foreach ($result as $record) {
    $nodes[$record->nid]->db_name = $record->name;
    $nodes[$record->nid]->db_path = $record->path;
  }
   
}

