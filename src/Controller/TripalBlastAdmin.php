<?php
/**
 * @file 
 * This is the controller for Tripal BLAST configuration and help.
 * Both pages are admin pages.
 */

namespace Drupal\tripal_blast\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines TripalBlastUI class.
 * 
 */
class TripalBlastAdmin extends ControllerBase {
  /**
   * Returns a render-able array to create Tripal BLAST configuration page.
   * @see hook_theme in tripal_blast.module.
   */
  public function configuration() {
    return [
      // Tripal BLAST Configuration page theme.
      '#theme' => 'theme-tripal-blast-help',
      '#attached' => []
    ];  
  }
  
  /**
   * Returns a render-able array to create Tripal BLAST help page.
   * A list of variables (context links presented in the interface) is used
   * and is defined in the hook_theme implementation of this module.
   * @see hook_theme in tripal_blast.module.
   */
  public function help() {
    return [
      // Tripal BLAST Help page theme.
      '#theme' => 'theme-tripal-blast-help',
      '#attached' => []
    ];  
  }
}