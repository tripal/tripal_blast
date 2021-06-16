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

}