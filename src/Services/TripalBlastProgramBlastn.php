<?php

namespace Drupal\tripal_blast\Services;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tripal blastn program class.
 */
class TripalBlastProgramBlastn {
  const BLASTN = 'blastn';

  use StringTranslationTrait;

  /**
   * Advanced field names used.
   *
   * Refer to this value when validating and submitting fields
   * under advanced options.
   */
  public function formFieldNames() {
    // Keys match field names used in form definition below.
    $field_name_validator = [
      'maxTarget' => [],
      'eVal' => ['number'],
      'wordSize' => [],
      'M&MScores' => [],
      'gapCost' => [],
    ];

    return $field_name_validator;
  }

  /**
   * Adds the blastn Advanced Options to the passed in form.
   *
   * This form function is meant to be called within another form definition.
   *
   * @param array $blast_cache
   *   BLAST job history to reference information contained.
   *
   * @return array
   *   Additional form field definitions.
   */
  public function formOptions(array $blast_cache): array {
    $blast = self::BLASTN;

    // Edit and Resubmit functionality.
    // We want to pull up the details from a previous blast and fill them in
    // as defaults for this blast.
    $options = (isset($blast_cache)) ? $blast_cache : [];
    $defaults = TripalBlastProgramHelper::programGetDefaultValues($options, $blast);

    $form_alter = [];
    $container = 'ALG';

    $form_alter[$container] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced Options'),
      '#open' => FALSE,
    ];

    // @todo previous job details.
    $form_alter[$container]['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General Parameters'),
      '#open' => TRUE,
    ];

    //
    // # FIELD: MAXIMUM TARGET.
    $max_target_options = TripalBlastProgramHelper::programGetMaxTarget($blast);
    $form_alter[$container]['general']['maxTarget'] = [
      '#type' => 'select',
      '#title' => $this->t('Max target sequences:'),
      '#options' => $max_target_options,
      '#default_value' => $defaults['max_target_seqs'],
      '#description' => $this->t('Select the maximum number of unique target sequences per
          query sequence to show results for. Results returned may not be the highest scoring hits.
          <a href="https://academic.oup.com/bioinformatics/article/35/9/1613/5106166" target="_blank">More Information</a>'),
    ];

    //
    // # FIELD: EVAL.
    $form_alter[$container]['general']['eVal'] = [
      '#type' => 'textfield',
      '#title' => $this->t('e-Value (Expected Threshold)'),
      '#default_value' => $defaults['evalue'],
      '#size' => 12,
      '#maxlength' => 20,
      '#description' => $this->t('Expected number of chance matches in a random model. This number should be give in a decimal format.
          <a href="https://www.ncbi.nlm.nih.gov/BLAST/blastcgihelp.shtml#expect" target="_blank">More Information</a> |
          <a href="https://www.youtube.com/watch?v=nO0wJgZRZJs" target="_blank">Expect value video tutorial</a>'),
    ];

    //
    // # FIELD: WORDSIZE.
    $word_size_options = TripalBlastProgramHelper::programGetWordSize($blast);
    $form_alter[$container]['general']['wordSize'] = [
      '#type' => 'select',
      '#title' => $this->t('Word size'),
      '#options' => $word_size_options,
      '#default_value' => $defaults['word_size'],
      '#description' => $this->t('The length of the seed that initiates an alignment'),
    ];

    $form_alter[$container]['scoring_param'] = [
      '#type' => 'details',
      '#title' => $this->t('Scoring parameters'),
      '#open' => TRUE,
    ];

    //
    // # FIELD: MATCH AND MISMATCH.
    $mm_options = TripalBlastProgramHelper::programGetMatchMismatch($blast);
    $form_alter[$container]['scoring_param']['M&MScores'] = [
      '#type' => 'select',
      '#title' => $this->t('Match/Mismatch Scores:'),
      '#options' => $mm_options,
      '#default_value' => $defaults['matchmiss'],
      '#description' => $this->t('Reward and penalty for matching and mismatching bases.'),
      '#ajax' => [
        'callback' => '::ajaxFieldUpdateCallback',
        'wrapper'  => 'tripal-blast-wrapper-fld-select-gap-cost',
        'method'   => 'replace',
        'event'    => 'change',
        'effect'   => 'fade',
        'progress' => 'throbber',
        'message'  => '',
      ],
    ];

    //
    // # FIELD: GAP COST.
    $gap_cost_options = TripalBlastProgramHelper::programGetGapCost($blast, $defaults['matchmiss']);
    $form_alter[$container]['scoring_param']['gapCost'] = [
      '#type' => 'select',
      '#title' => $this->t('Gap Costs:'),
      '#options' => $gap_cost_options,
      '#default_value' => $defaults['gap'],
      '#description' => $this->t('Cost to create and extend a gap in an alignment.'),
      '#id' => 'tripal-blast-fld-select-gap-cost',
      '#prefix' => '<div id="tripal-blast-wrapper-fld-select-gap-cost">',
      '#suffix' => '</div>',
    ];

    return $form_alter;
  }

  /**
   * Map advanced options specific to this program to BLAST keywords.
   *
   * @param array $advanced_field_values
   *   Values set from form ($form_state).
   *
   * @return array
   *   Form values mapped to BLAST keywords.
   */
  public function formFieldBlastKey($advanced_field_values) {
    $eval = $advanced_field_values['eVal'] ?? '';
    $max_target = $advanced_field_values['maxTarget'] ?? '';
    $word_size = $advanced_field_values['wordSize'] ?? '';
    $gap_key = $advanced_field_values['gapCost'] ?? '';

    $gap = TripalBlastProgramHelper::programSetGap($gap_key);
    $gap_open = $gap['gapopen'];
    $gap_extend = $gap['gapextend'];

    $mm = TripalBlastProgramHelper::programSetMatchMismatch($advanced_field_values['M&MScores'] ?? '');
    $penalty = $mm['penalty'] ?? '';
    $reward = $mm['reward'] ?? '';

    return [
      'max_target_seqs' => $max_target,
      'evalue' => $eval,
      'word_size' => $word_size,
      'gapopen' => $gap_open,
      'gapextend' => $gap_extend,
      'penalty' => $penalty,
      'reward' => $reward,
    ];
  }

}
