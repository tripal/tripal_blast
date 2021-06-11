<?php
/**
 * @file 
 * Construct Tripal BLAST form. 
 */

namespace Drupal\tripal_blast\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
/**
 * Define Tripal BLAST form.
 */
class TripalBlastForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tripalblastform';
  }

  /**
   * {@inheritdoc}
   * Build form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $query = NULL, $program = NULL) {    
    $database_service = \Drupal::service('tripal_blast.database_service');
    $options = $database_service->getDatabaseByType();
    
    //@TODO: BLAST FORM HERE
   
    return $form;
  }

  /**
   * {@inheritdoc}
   * Save configuration.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }
}