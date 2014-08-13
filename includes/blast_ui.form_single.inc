<?php

/**
 * @file
 *
 */

/**
 *
 */
function blast_ui_all_in_one_form($form, $form_state) {

  // Give the users a message so they don't realize this was supposed to be a
  // form that just isn't ready yet.
  $form['msg'] = array(
    '#type' => 'item',
    '#markup' => theme('blast_user_menupage')
  );

  return $form;
}