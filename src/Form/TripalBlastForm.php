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
    // Add a warning, if need be (to be used for temporary message like down-for-maintenance).
    $config_warning_text = \Drupal::config('tripal_blast.settings')
      ->get('tripal_blast_config_notification.warning_text');
    
    if ($config_warning_text) {
      $form['warning'] = [
        '#type' => 'inline_template',
        '#template' => '
          <div role="contentinfo" aria-label="Warning message" class="messages messages--warning">
            <div role="alert">
              <h2 class="visually-hidden">Warning message</h2>
              '. $config_warning_text .'
            </div>
          </div>
        '
      ];  
    }

    // Attach a service:
    $database_service = \Drupal::service('tripal_blast.database_service');
    
    // Set the title to be more Researcher friendly.
    $program_name = $database_service->getProgramName($query, $program);
    $page_title = [
      '@query' => ucfirst($query), 
      '@program' => ucfirst($program), 
      '@name' => $program_name
    ];
    $form['#title'] = $this->t('@query to @program BLAST (@name)', $page_title);
    
    // Add the details about the specific BLAST choosen.
    $form['query'] = [
      '#type' => 'hidden',
      '#value' => $query
    ];

    $form['program'] = [
      '#type' => 'hidden',
      '#value' => $program
    ];

    $form['program_name'] = [
      '#type' => 'hidden',
      '#value' => $program_name
    ];

    $form['new_blast'] = [
      '#type' => 'details',
      '#title' => $this->t('Request a New BLAST'),
      '#open' => TRUE,
    ];
















    //////
    
    $blast_db = $database_service->getDatabaseByType($program);
    

    //var_dump($blast_program);
    return $form;
  }

  /**
   * {@inheritdoc}
   * Save configuration.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }
}