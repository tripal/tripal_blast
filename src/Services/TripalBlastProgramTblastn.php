<?php
/**
 * @file
 * Form definition of tBLASTn program.
 */

namespace Drupal\tripal_blast\Services;

use Drupal\tripal_blast\Services\TripalBlastProgramHelper;

/**
 * tBLASTn program class.
 */
class TripalBlastProgramTblastn {
  const tBLASTn = 'tblastn';

  /**
   * Advanced field names used - refer to this value when
   * validating and submitting fields under advanced options.
   */
  public function formFieldNames() {
    // Keys match field names used in form definition below.
    $field_name_validator = [
      'maxTarget' => [],
      'eVal' => ['number'],
      'wordSize' => [],
      'matrix' => []
    ];

    return $field_name_validator;
  }

  /**
   * Adds the tBLASTn Advanced Options to the passed in form.
   * This form function is meant to be called within another form definition.
   *
   * @param $blast_cache
   *   BLAST job history to reference information information contained.
   *
   * @return array
   *   Additional form field definitions.
   */
  public function formOptions($blast_cache) {
    $blast = self::tBLASTn;

    // Edit and Resubmit functionality.
    // We want to pull up the details from a previous blast and fill them in as defaults
    // for this blast.
    $options = (isset($blast_cache)) ? $blast_cache : [];
    $defaults = TripalBlastProgramHelper::programGetDefaultValues($options, $blast);

    $form_alter = [];
    $container = 'ALG' ;

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
      $max_target_options = TripalBlastProgramHelper::programGetMaxTarget($blast);
      $form_alter[ $container ]['general']['maxTarget'] = [
        '#type' => 'select',
        '#title' => t('Max target sequences:'),
        '#options' => $max_target_options,
        '#default_value' => $defaults['max_target_seqs'],
        '#description' => t('Select the maximum number of unique target sequences per
          query sequence to show results for. Results returned may not be the highest scoring hits.
          <a href="https://academic.oup.com/bioinformatics/article/35/9/1613/5106166" target="_blank">More Information</a>'),
      ];

      //
      // # FIELD: EVAL.
      $form_alter[ $container ]['general']['eVal'] = [
        '#type' => 'textfield',
        '#title' => t('e-Value (Expected Threshold)'),
        '#default_value' => $defaults['evalue'],
        '#size' => 12,
        '#maxlength' => 20,
        '#description' => t('Expected number of chance matches in a random model. This number should be give in a decimal format.
          <a href="https://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml#expect" target="_blank">More Information</a> |
          <a href="https://www.youtube.com/watch?v=nO0wJgZRZJs" target="_blank">Expect value video tutorial</a>'),
      ];

      //
      // # FIELD: WORDSIZE.
      $word_size_options = TripalBlastProgramHelper::programGetWordSize($blast);
      $form_alter[ $container ]['general']['wordSize'] = [
        '#type' => 'select',
        '#title' => t('Word size'),
        '#options' => $word_size_options,
        '#default_value' => $defaults['word_size'],
        '#description' => t('The length of the seed that initiates an alignment'),
      ];

    $form_alter[ $container ]['scoring_param'] = [
      '#type' => 'details',
      '#title' => t('Scoring parameters'),
      '#open' => TRUE,
    ];

      //
      // # FIELD: MATRIX
    $matrix_options = TripalBlastProgramHelper::programGetScoringMatrix($blast);
      $form_alter[ $container ]['scoring_param']['matrix'] = [
        '#type' => 'select',
        '#title' => t('Matrix:'),
        '#options' => $matrix_options,
        '#default_value' => $defaults['matrix'],
        '#description' => t('Assigns a score for aligning pairs of residues, and determines overall alignment score.'),
      ];

    return $form_alter;
  }

  /**
   * Map advanced options specific to this program to BLAST keywords.
   *
   * @param $advanced_field_names
   *   Values set from form ($form_state).
   *
   * @return array
   *   Form values mapped to BLAST keywords.
   */
  public function formFieldBlastKey($advanced_field_values) {
    $eval = $advanced_field_values['eVal'];
    $max_target = $advanced_field_values['maxTarget'];
    $word_size = $advanced_field_values['wordSize'];
    $matrix = $advanced_field_values['matrix'];

    return [
      'max_target_seqs' => $max_target,
      'evalue' => $eval,
      'word_size' => $word_size,
      'matrix' => $matrix
    ];
  }
}
