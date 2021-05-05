<?php
/**
 * @file 
 * Construct BLAST UI Help Page.
 */

namespace Drupal\blast_ui\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines TripalBlastUIBlastHelp class.
 */
class TripalBlastUIBlastHelp extends ControllerBase {
  /**
   * Returns a render-able array for submission form.
   */
  public function content() {
    return [
      '#theme' => 'theme-blast-ui-help',
      '#attached' => [
        'library' => [],
        'drupalSettings' => []
      ]      
    ];  
  }    
}