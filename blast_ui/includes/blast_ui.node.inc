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
    // Add a #description for each field
    '#required' => TRUE,
    '#default_value' => NULL,
  );
  $form['db_path']= array(
    '#type' => 'textfield',
    '#title' => t('File Address'),
    '#required' => TRUE,
    '#default_value' => NULL,
  );

  $form['db_common_name']= array(
    '#type' => 'textfield',
    // this is a description; you want something more succint for a title such as File Name
    '#title' => t('Pick a friendly name for your file'),
    '#required' => TRUE,
    '#default_value' => NULL,
  );

  return $form;
}
