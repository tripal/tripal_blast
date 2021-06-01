<?php
/**
 * @file 
 * This route is for single all-in-one BLAST user interface.
 * Choices of query type, protein or nucleotide, outlined in this page.
 */

namespace Drupal\tripal_blast\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines TripalBlastUI class.
 * 
 */
class TripalBlastUI extends ControllerBase {
  /**
   * Returns a render-able array to create Tripal BLAST UI elements.
   * A list of variables (context links presented in the interface) is used
   * and is defined in the hook_theme implementation of this module.
   * @see hook_theme in tripal_blast.module.
   */
  public function content() {
    return [
      // Tripal BLAST UI page theme.
      '#theme' => 'theme-tripal-blast-ui',
      '#attached' => [
        'library' => ['tripal_blast/tripal-blast-ui']
      ]
    ];  
  }    
}