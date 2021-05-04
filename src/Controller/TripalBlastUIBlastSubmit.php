<?php
/**
 * @file 
 * Construct single all-in-one BLAST submission form.
 */

namespace Drupal\blast_ui\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines TripalBlastUIBlastSubmit class.
 */
class TripalBlastUIBlastSubmit extends ControllerBase {
  /**
   * Returns a render-able array for submission form.
   */
  public function content() {
    return [
      '#theme' => 'theme-blast-ui-menupage',
      '#attached' => [
        'library' => [],
        'drupalSettings' => []
      ]      
    ];  
  }    
}