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
   */
  public static function programGetMaxTarget($program_name) {
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







}