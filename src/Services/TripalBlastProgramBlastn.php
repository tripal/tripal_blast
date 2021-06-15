<?php
/**
 * @file
 * Form definition of BLASTn program.
 */

namespace Drupal\tripal_blast\Services;

use Drupal\tripal_blast\Services\TripalBlastProgramHelper;

 /**
  * BLASTn program class.
  */
class TripalBlastProgramBlastn {
  /**
   * Adds the BLASTn Advanced Options to the passed in form.
   *
   * This form function is meant to be called within another form definition.
   *
   * @param $form
   *   The form the advanced options should be added to. This form already
   *   contains a fieldset meant to contain the advanced options.
   * @param $formstate
   *   The current state fo the form passed in as $form.
   * @param $program_name
   *   String, established program name - blastn, blastx, blastp and tblastn.
   * @param $container
   *   The fieldset/details form element that will contain all field defined.
   *   Default to advanced_options.
   */  
  public function formOptions($program_name, $container = 'advanced_options') {
    // @TODO: previous job details.
    $form[ $container ]['general'] = [
      '#type' => 'details',
      '#title' => t('General Parameters'),
      '#open' => FALSE
    ];

      //
      // FIELD: MAXIMUM TARGET.
      $max_target = TripalBlastProgramHelper::programGetMaxTarget($program_name);
      $form[ $container ]['general']['fld_select_max_target'] = [
        '#type' => 'select',
        '#title' => t('Max target sequences:'),
        '#options' => $max_target,
        //'#default_value' => $defaults['max_target_seqs'],
        '#description' => t('Select the maximum number of unique target sequences per query sequence to show results for. Results returned may not be the highest scoring hits. <a href="https://academic.oup.com/bioinformatics/article/35/9/1613/5106166" target="_blank">More Information</a>'),
      ];

      
      //\Drupal::formBuilder()->getForm('Drupal\tripal_blast\Form\TripalBlastForm', $form);


  }

  /**
   * Program form field validate hook.
   */  
  public function formValidate($form, $form_state) {

  }

  /**
   * Program form sumbit hook.
   */  
  public function formSubmit($form, $form_state) {

  }
}