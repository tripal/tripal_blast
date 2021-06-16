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
   * This form function is meant to be called within another form definition.
   *
   * @param $program_name
   *   String, established program name - blastn, blastx, blastp and tblastn.
   * 
   * @return array
   *   Additional form field definitions.
   */  
  public function formOptions($program_name) {
    $form_alter = [];
    
    $container = 'advanced_options' ; 

    $form_alter[ $container ] = [
      '#type' => 'details',
      '#title' => t('Advanced Options'),
      '#open' => FALSE
    ];

    // @TODO: previous job details.
    $form_alter[ $container ]['general'] = [
      '#type' => 'details',
      '#title' => t('General Parameters'),
      '#open' => TRUE
    ];

      //
      // # FIELD: MAXIMUM TARGET.
      $max_target = TripalBlastProgramHelper::programGetMaxTarget($program_name);
      $form_alter[ $container ]['general']['fld_select_max_target'] = [
        '#type' => 'select',
        '#title' => t('Max target sequences:'),
        '#options' => $max_target,
        //'#default_value' => $defaults['max_target_seqs'],
        '#description' => t('Select the maximum number of unique target sequences per 
          query sequence to show results for. Results returned may not be the highest scoring hits. 
          <a href="https://academic.oup.com/bioinformatics/article/35/9/1613/5106166" target="_blank">More Information</a>'),
      ];

      //
      // # FIELD: EVAL.
      $form_alter[ $container ]['general']['fld_text_eval'] = [
        '#type' => 'textfield',
        '#title' => t('e-Value (Expected Threshold)'),
        // '#default_value' => $defaults['evalue'],
        '#size' => 12,
        '#maxlength' => 20,
        '#description' => t('Expected number of chance matches in a random model. This number should be give in a decimal format. 
          <a href="https://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml#expect" target="_blank">More Information</a> | 
          <a href="https://www.youtube.com/watch?v=nO0wJgZRZJs" target="_blank">Expect value video tutorial</a>'),
      ];

      //
      // # FIELD: WORDSIZE
      $word_size = TripalBlastProgramHelper::programGetWordSize($program_name);
      $form_alter[ $container ]['general']['fld_select_word_size'] = [
        '#type' => 'select',
        '#title' => t('Word size'),
        '#options' => $word_size,
        //'#default_value' => $defaults['word_size'],
        '#description' => t('The length of the seed that initiates an alignment'),
      ];

    $form_alter[ $container ]['scoring_param'] = [
      '#type' => 'details',
      '#title' => t('Scoring parameters'),
      '#open' => TRUE,
    ];
      
      //
      // # FIELD: MATRIX.
      $matrix_options = TripalBlastProgramHelper::programGetScoringMatrix($program_name);
      $form_alter[ $container ]['scoring_param']['fld_select_matrix'] = [
        '#type' => 'select',
        '#title' => 'Matrix',
        '#options' => $matrix_options,
        // '#default_value' => $defaults['matrix'],
        '#description' => t('Assigns a score for aligning pairs of residues, and determines overall alignment score.'),
        //'#ajax' => array(
          //'callback' => 'matrix_gap_cost_callback',
          //'wrapper' => 'gap_cost_wrapper',
       // ),
      ];
  
    


    return $form_alter;
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