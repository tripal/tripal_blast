<?php

namespace Drupal\tripal_blast\Services;

/**
 * Tripal blast program helper class.
 */
class TripalBlastProgramHelper {

  /**
   * Get a list of options for the max_target_seq blast option.
   *
   * The options are the same for all programs
   * and describe the maximum number of aligned sequences to keep.
   *
   * @param string $program_name
   *   Established program name - blastn, blastx, blastp, or tblastn.
   *
   * @return array
   *   Number range.
   */
  public static function programGetMaxTarget(string $program_name): array {
    $max_target = 0;

    switch ($program_name) {
      case 'blastn':
      case 'blastx':
      case 'blastp':
      case 'tblastn':
        $max_target = [
          0     => ' ',
          10    => t('10'),
          50    => t('50'),
          100   => t('100'),
          250   => t('250'),
          500   => t('500'),
          1000  => t('1000'),
          5000  => t('5000'),
          10000 => t('10000'),
          20000 => t('20000'),
        ];
    }

    return $max_target;
  }

  /**
   * Create options for word size per program.
   *
   * @param string $program_name
   *   Established program name - blastn, blastx, blastp, or tblastn.
   *
   * @return array
   *   Number range.
   */
  public static function programGetWordSize(string $program_name): array {
    $word_size = 0;

    switch ($program_name) {
      case 'blastn':
        $word_size = [
          7 => t('7'),
          11 => t('11'),
          15 => t('15'),
          16 => t('16'),
          20 => t('20'),
          24 => t('24'),
          28 => t('28'),
          32 => t('32'),
          48 => t('48'),
          64 => t('64'),
          128 => t('128'),
          256 => t('256'),
        ];

        break;

      case 'blastx':
      case 'blastp':
      case 'tblastn':
        $word_size = [
          // 2 => t('2'),
          3 => t('3'),
          6 => t('6'),
        ];
    }

    return $word_size;
  }

  /**
   * Create options for matrix per program.
   *
   * @param string $program_name
   *   Established program name - blastn, blastx, blastp, or tblastn.
   *
   * @return array
   *   Matrix ids/keys.
   */
  public static function programGetScoringMatrix(string $program_name): array {
    $scoring_matrix = [
      'PAM30',
      'PAM70',
      'PAM250',
      'BLOSUM80',
      'BLOSUM62',
      'BLOSUM45',
      'BLOSUM50',
      'BLOSUM90',
    ];

    return array_combine($scoring_matrix, $scoring_matrix);
  }

  /**
   * Fill the gap penalty dropdown list with appropriate options.
   *
   * Options depend on the provided matrix key.
   *
   * @param string $key
   *   String, matrix id or key defined in programGetScoringMatrix().
   *
   * @return array
   *   An array containing open and extension gap values for the chosen matrix
   *   (to fill the second dropdown list).
   *
   * @dependencies
   *   programGetScoringMatrix() and programMakeGaps().
   */
  public static function programGetGapForMatrix(string $key = ''): array {
    $matrix_gap = [];

    switch ($key) {
      case 'PAM30':
        $matrix_gap = ['9_1', '8_2', '7_2'];
        break;

      case 'PAM70':
        $matrix_gap = ['10_1', '9_2', '8_2'];
        break;

      case 'PAM250':
        $matrix_gap = ['11_1', '10_1', '10_2', '9_2'];
        break;

      case 'BLOSUM90':
        $matrix_gap = ['11_1', '10_1', '9_2', '8_2'];
        break;

      case 'BLOSUM80':
        $matrix_gap = ['10_1', '9_2', '8_2'];
        break;

      case 'BLOSUM62':
        $matrix_gap = ['11_1', '10_1', '10_2', '9_2', '8_2'];
        break;

      case 'BLOSUM50':
        $matrix_gap = ['11_1', '10_1', '10_2', '9_2'];
        break;

      case 'BLOSUM45':
        $matrix_gap = ['10_2', '9_2', '8_2'];
        break;
    }

    return TripalBlastProgramHelper::programMakeGap($matrix_gap);
  }

  /**
   * Expand matrix gap per matrix key gap array (value 1 _ value 2).
   *
   * @param array $gap_array
   *   Array of gap abbreviations base on the matrix key.
   *
   * @return array
   *   Deconstructed gap (value 1 and value 2).
   *
   * @see programGetGapForMatrix()
   */
  public static function programMakeGap(array $gap_array): array {
    $gap = [];

    foreach ($gap_array as $value) {
      [$value1, $value2] = explode('_', $value);
      $gap[$value] = t('Existence: @value1 Extension: @value2', ['@value1' => $value1, '@value2' => $value2]);
    }

    return $gap;
  }

  /**
   * Translate gap abbreviation into blast gap open and extend costs.
   *
   * @param string $gap_key
   *   A gap open/extend abbreviation.
   */
  public static function programSetGap(string $gap_key) {
    $parts = explode('_', (string) $gap_key);

    return [
      'gapopen' => $parts[0] ?? '',
      'gapextend' => $parts[1] ?? '',
    ];
  }

  /**
   * Translate mismatch/match ratio option into blast penalty/reward options.
   *
   * @param int $mm_score
   *   Match and mismatch value.
   */
  public static function programSetMatchMismatch(int $mm_score) {
    // Defaults in case an unexpected value is passed.
    $penalty = '';
    $reward = '';

    switch ($mm_score) {
      case 0:
        $penalty = -2;
        $reward = 1;
        break;

      case 1:
        $penalty = -3;
        $reward = 1;
        break;

      case 2:
        $penalty = -4;
        $reward = 1;
        break;

      case 3:
        $penalty = -3;
        $reward = 2;
        break;

      case 4:
        $penalty = -5;
        $reward = 4;
        break;

      case 5:
        $penalty = -1;
        $reward = 1;
        break;
    }

    return ['penalty' => $penalty, 'reward' => $reward];
  }

  /**
   * Reward and penalty for matching and mismatching bases.
   *
   * @param string $program_name
   *   Established program name - blastn, blastx, blastp, or tblastn.
   *
   * @return string|array
   *   Mismatch array if blastn, empty string for other programs.
   */
  public static function programGetMatchMismatch(string $program_name): string|array {
    $mm = [
      'blastn' => [
        0 => '1,-2',
        1 => '1,-3',
        2 => '1,-4',
        3 => '2,-3',
        4 => '4,-5',
        5 => '1,-1',
      ],
    ];

    return $mm[$program_name] ?? '';
  }

  /**
   * Cost to create and extend a gap in an alignment.
   *
   * @param string $program_name
   *   Established program name - blastn, blastx, blastp, or tblastn.
   * @param int $mm_set
   *   Value selected in Match/Mismatch field (as default).
   */
  public static function programGetGapCost(string $program_name, int $mm_set) {
    $gap = [];

    switch ($program_name) {
      case 'blastn':
        switch ($mm_set) {
          // 1, -2
          case 0:
            $gap = ['5_2', '2_2', '1_2', '0_2', '3_1', '2_1', '1_1'];
            break;

          // 1, -3
          case 1:
            $gap = ['5_2', '2_2', '1_2', '1_2', '0_2', '2_1', '1_1'];
            break;

          // 1, -4
          case 2:
            $gap = ['5_2', '1_2', '0_2', '2_1', '1_1'];
            break;

          // 2, -3
          case 3:
            $gap = ['4_4', '2_4', '0_4', '3_3', '6_2', '5_2', '4_2', '2_2'];
            break;

          // 4, -5
          case 4:
            $gap = ['12_8', '6_5', '5_5', '4_5', '3_5'];
            break;

          // 1, -1
          case 5:
            $gap = ['5_2', '3_2', '2_2', '1_2', '0_2', '4_1', '3_1', '2_1'];
        }
    }

    return TripalBlastProgramHelper::programMakeGap($gap);
  }

  /**
   * FASTA validating parser.
   *
   * A sequence in FASTA format begins with a single-line description, followed
   * by lines of sequence data.The description line is distinguished from the
   * sequence data by a greater-than (">") symbol in the first column. The word
   * following the ">" symbol is the identifier of the sequence, and the rest of
   * the line is the description (both are optional). There should be no space
   * between the ">" and the first letter of the identifier. The sequence ends
   * if another line starting with a ">" appears which indicates the start of
   * another sequence.
   *
   * @param string $query
   *   The type of sequence to be validated, i.e. either
   *   'nucleotide' or 'protein'.
   * @param string $fasta_sequence
   *   A string of characters to be validated.
   *
   * @return bool
   *   Return TRUE if the sequence passes the format valifation
   *   and FALSE if not valid.
   */
  public static function programValidateFastaSequence(string $query, string $fasta_sequence) {
    // Includes IUPAC codes.
    $fastaSeqRegEx = ($query == 'nucleotide')
      ? '/^[ATCGNUKMBVSWDYRHatcgnukmbvswdyrh\[\/\]\s\n\r]*$/'
      : '/^[acdefghiklmnpqrstvwyACDEFGHIKLMNPQRSTVWY\*\-\s\n\r]*$/';

    $defRegEx = '/^>\S.*/';

    // For each line of the sequence.
    foreach (explode("\n", $fasta_sequence) as $line) {
      if (isset($line[0]) && $line[0] == '>') {
        // Is this a definition line?
        if (!preg_match($defRegEx, $line)) {
          return FALSE;
        }
      }
      else {
        // Otherwise it's a sequence line.
        if (!preg_match($fastaSeqRegEx, $line)) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

  /**
   * Validate field callback. Used for the e-value field.
   *
   * @param string $value
   *   Value to validate to match the type.
   * @param string $type
   *   Value type to test a given value, only 'number' is supported.
   *
   * @return array
   *   Array specifying validity. Keys are:
   *   - result: boolean, TRUE if valid, FALSE if not valid.
   *   - message: A message if not valid, empty string if valid.
   */
  public static function programValidateValue(string $value, string $type) {
    $is_valid = [
      'result' => TRUE,
      'message' => '',
    ];

    switch ($type) {
      case 'number':
        if (!is_numeric($value)) {
          $is_valid['result'] = FALSE;
          $is_valid['message'] = 'The e-value should be a very small number (scientific notation is supported).
            For example, <em>0.001</em> or, even better, <em>1e-10</em>.';
        }

        break;
    }

    return $is_valid;
  }

  /**
   * Get default form values.
   *
   * The values may come from saved job data if user is re-running
   * a previous job.
   *
   * @param array $options
   *   Various blast query options, and if not set, assign an
   *   appropriate default value. Array keys are:
   *     - max_target_seqs
   *     - short_queries
   *     - word_size
   *     - evalue
   *     - matchmiss
   *     - gap
   *     - matrix.
   * @param string $program
   *   Established program name - blastn, blastx, blastp, or tblastn.
   *
   * @return array
   *   The $options array with all keys having passed or default values.
   */
  public static function programGetDefaultValues($options, $program) {
    // Restore previous values or set to default.
    $max_target = (isset($options['max_target_seqs']))
      ? $options['max_target_seqs'] : 500;

    $short_queries = (isset($options['shortQueries']))
      ? $options['shortQueries'] : TRUE;

    $eval = \Drupal::config('tripal_blast.settings')
      ->get('tripal_blast_config_general.eval');
    $evalue = (isset($options['evalue']))
      ? $options['evalue'] : $eval;

    $word_size = (isset($options['word_size']))
      ? $options['word_size'] : 11;

    // match/mismatch.
    $matchmiss = 0;
    $reward = (isset($options['reward']))
      ? $options['reward'] : 1;

    $penalty = (isset($options['penalty']))
      ? $options['penalty'] : -2;

    if ($reward == 1) {
      switch ($penalty) {
        case -1:
          $matchmiss = 5;
          break;

        case -2:
          $matchmiss = 0;
          break;

        case -3:
          $matchmiss = 1;
          break;

        case -4:
          $matchmiss = 2;
          break;
      }
    }
    else {
      if ($reward == 2) {
        $matchmiss = 3;
      }
      else {
        if ($reward == 3) {
          $matchmiss = 4;
        }
        else {
          if ($reward == 4) {
            $matchmiss = 5;
          }
        }
      }
    }

    // Gap.
    if (isset($options['gapopen']) && isset($options['gapextend'])) {
      $gapopen = $options['gapopen'];
      $gapextend = $options['gapextend'];
    }
    else {
      switch ($program) {
        case 'blastn':
          $gapopen = 5;
          $gapextend = 2;
          break;

        case 'blastp':
        case 'blastx':
        case 'tblastn':
          $gapopen = 11;
          $gapextend = 1;
          break;
      }
    }
    $gap = $gapopen . '_' . $gapextend;

    // Matrix.
    $matrix = (isset($options['matrix']))
      ? $options['matrix'] : 'BLOSUM62';

    // All done.
    return [
      'max_target_seqs' => $max_target,
      'short_queries' => $short_queries,
      'word_size' => $word_size,
      'evalue' => $evalue,
      'matchmiss' => $matchmiss,
      'gap' => $gap,
      'matrix' => $matrix,
    ];
  }

}
