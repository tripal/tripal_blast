<?php
/**
 * @file
 * Contains methods to assist advanced field elements in a program
 * fetch options, configuration and default values.
 */

namespace Drupal\tripal_blast\Services;

 /**
  * BLAST program heloer class.
  */
class TripalBlastProgramHelper {
  /**
   * Get a list of options for the max_target_seq blast option.
   *
   * The options are the same for all programs
   * and describe the maximum number of aligned sequences to keep.
   * 
   * @param $program_name
   *   String, established program name - blastn, blastx, blastp and tblastn.
   * 
   * @param array
   *   Number range.
   */
  public static function programGetMaxTarget($program_name) {
    $max_target = 0;

    switch($program_name) {
      case 'blastn' :
      case 'blastx' :
      case 'blastp' :
      case 'tblastn':
        $max_target = [
          0     => t(' '),
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
   * @param $program_name
   *   String, established program name - blastn, blastx, blastp and tblastn.
   * 
   * @param array
   *   Number range.
   */
  public static function programGetWordSize($program_name) {
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
          //  2 => t('2'),
          3 => t('3'),
          6 => t('6'),
        ];
    }

    return $word_size;
  }

  /**
   * Create options for matrix per program.
   *
   * @param $program_name
   *   String, established program name - blastn, blastx, blastp and tblastn.
   * 
   * @param array
   *   Matrix ids/keys.
   */
  public static function programGetScoringMatrix($program_name) {
    $scoring_matrix = [
      t('PAM30'),
      t('PAM70'),
      t('PAM250'),
      t('BLOSUM80'),
      t('BLOSUM62'),
      t('BLOSUM45'),
      t('BLOSUM50'),
      t('BLOSUM90')
    ];

    return array_combine($scoring_matrix, $scoring_matrix);
  }

  /**
   * Fill the gap penalty dropdown list with appropriate options given a selected matrix.
   * 
   * @param $key
   *   String, matrix id or key defined in programGetScoringMatrix().
   *
   * @return
   *   An array containing open and extension gap values for the chosen matrix 
   *   (to fill the second dropdown list)
   * 
   * @dependencies
   *   programGetScoringMatrix() and programMakeGaps().
   */
  public static function programGetGapForMatrix($key = '') {
    $matrix_gap = '';

    switch ($key) {
      case 'PAM30':
        $matrix_gap = ['7_2', '6_2', '5_2', '10_1', '8_1', '13_3', '15_3', '14_1', '14_2'];
        break;

      case 'PAM70':
        $matrix_gap = ['8_2', '7_2', '6_2', '11_1', '10_1', '9_1', '12_3', '11_2'];
        break;
      
      case 'PAM250':
        $matrix_gap = ['15_3', '14_3', '13_3', '12_3', '11_3', '17_2', '16_2', '15_2', 
                       '14_2', '13_2', '21_1', '20_1', '19_1', '18_1', '17_1'];
        break;

      case 'BLOSUM80':
        $matrix_gap = ['8_2', '7_2', '6_2', '11_1', '10_1', '9_1'];
        break;

      case 'BLOSUM62':
        $matrix_gap = ['11_2', '10_2', '9_2', '8_2', '7_2', '6_2', '13_1', '12_1', '11_1', '10_1', '9_1'];
        break;

      case 'BLOSUM45':
        $matrix_gap = ['13_3', '12_3', '11_3', '10_3', '15_2', '14_2', '13_2', '12_2', '19_1', 
                       '18_1', '17_1', '16_1'];
        break;

      case 'BLOSUM50':
        $matrix_gap = ['13_3', '12_3', '11_3', '10_3', '9_3', '16_2', '15_2', '14_2', '13_2', '12_2', 
                       '19_1', '18_1', '17_1', '16_1', '15_1'];
        break;

      case 'BLOSUM90':
        $matrix_gap = ['9_2', '8_2', '7_2', '6_2', '11_1', '10_1', '9_1'];
    }
    
    return TripalBlastProgramHelper::programMakeGap($matrix_gap);
  }

  /**
   * Expand matrix gap per matrix key gap array (value 1 _ value 2).
   * 
   * @param $gap_array 
   *   Array of gap abbreviations base on the matrix key.
   *
   * @return array
   *   Deconstructed gap (value 1 and value 2).
   * 
   * @see
   *   programGetGapForMatrix().
   */
  public static function programMakeGap($gap_array) {
    $gap = [];
     
    foreach($gap_array as $value) {
      list($value1, $value2) = explode('_', $value);
      $gap[ $value ] = t('Existence: @value1 Extension: @value2', ['@value1' => $value1, '@value2' => $value2]);
    }

    return $gap;
  }

  /**
   * Translate gap abbreviation into blast gap open and extend costs.
   * @param $gap_key 
   *   A gap open/extend abbreviation
   */
  public static function programSetGap($gap_key) {
    $parts = explode('-', $gap_key);
    return ['gapOpen' => $parts[0], 'gapExtend' => $parts[1]];
  }

  /**
   * Translate mismatch/match ratio option into blast penalty/reward options. 
   * 
   * @param $mm_score
   *   Match and mismatch value.
   */
  public static function programSetMatchMismatch($mm_score) {
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
   * @param $program_name
   *   String, established program name - blastn, blastx, blastp and tblastn.
   * 
   * @return array
   */
  public static function programGetMatchMismatch($program_name) {
    $mm = [
      'blastn' => [
        0 => t('1,-2'),
        1 => t('1,-3'),
        2 => t('1,-4'),
        3 => t('2,-3'),
        4 => t('4,-5'),
        5 => t('1,-1')
      ]
    ];
    
    return $mm[ $program_name ] ?? '';
  }

  /**
   * Cost to create and extend a gap in an alignment. 
   * 
   * @param $program_name
   *   String, established program name - blastn, blastx, blastp and tblastn.
   * @param $mm_set
   *   Value selected in Match/Mismatch field (as default). 
   */
  public static function programGetGapCost($program_name, $mm_set) {
    $gap = [];

    switch ($program_name) {
      case 'blastn':
        switch ($mm_set) {
          case 0: //1, -2
            $gap = ['5_2', '2_2', '1_2', '0_2', '3_1', '2_1', '1_1'];
            break;

          case 1: //1, -3
            $gap = ['5_2', '2_2', '1_2', '1_2', '0_2', '2_1', '1_1'];
            break;

          case 2: // 1, -4
            $gap = ['5_2', '1_2', '0_2', '2_1', '1_1'];
            break;

          case 3: //2, -3
            $gap = ['4_4', '2_4', '0_4', '3_3', '6_2', '5_2', '4_2', '2_2'];
            break;

          case 4: //4, -5
            $gap = ['12_8', '6_5', '5_5', '4_5', '3_5'];
            break;

          case 5: //1, -1
            $gap = ['5_2', '3_2', '2_2', '1_2', '0_2', '4_1', '3_1', '2_1'];
        }        
    }

    return TripalBlastProgramHelper::programMakeGap($gap);
  } 
  
  /**
   * FASTA validating parser
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
   * @param $query
   *   The type of sequence to be validated (ie: either nucleotide or protein).
   * @param $fasta_sequence
   *  A string of characters to be validated.
   *
   * @return
   *  Return a boolean. 1 if the sequence does not pass the format valifation stage and 0 otherwise.
   */
  public static function programValidateFastaSequence($query, $fasta_sequence) {
    // Includes IUPAC codes.
    $fastaSeqRegEx = ($query == 'nucleotide')
      ? '/^[ATCGNUKMBVSWDYRHatcgnukmbvswdyrh\[\/\]\s\n\r]*$/'
      : '/^[acdefghiklmnpqrstvwyACDEFGHIKLMNPQRSTVWY\*\-\s\n\r]*$/';
    
      $defRegEx      = '/^>\S.*/';

    // For each line of the sequence.
    foreach (explode("\n", $fasta_sequence) as $line) {      
      if (isset($line[0]) && $line[0] == '>') {
        // Is this a definition line?
        if (!preg_match($defRegEx, $line)) {
          return FALSE;
        }
      }
      else {
        // Otherwise it's a sequence line
        if (!preg_match($fastaSeqRegEx, $line)) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

  /**
   * Validate field callback.
   * 
   * @param $value
   *   Value to validate to match the type.
   * @param $type
   *   Value type to test a given value.
   * 
   * @return boolean
   *   True if value and type match, false otherwise.
   */
  public static function programValidateValue($value, $type) {
    $is_valid = [
      'result' => TRUE,
      'message' => ''
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
   * Get default form values; may come from saved job data if user is re-running
   * a previous job.
   * 
   * @param $options
   * @param $program
   */
  public static function programGetDefaultValues($options, $program) {
    // restore previous values or set to default
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

    // match/mismatch
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

    // gap
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
    $gap = $gapopen.'_'.$gapextend;
  
    // matrix
    $matrix = (isset($options['matrix']))
      ? $options['matrix'] : 'BLOSUM62';

    // all done
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