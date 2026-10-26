<?php

namespace Drupal\tripal_blast\Services;

/**
 * Tripal tblastn program class.
 */
class TripalBlastProgramTblastn {
  const TBLASTN = 'tblastn';

  /**
   * Advanced field names used.
   *
   * Refer to this value when validating and submitting fields
   * under advanced options.
   */
  public function formFieldNames() {
    // Keys match field names used in form definition below.
    $field_name_validator = [];
    return $field_name_validator;
  }

  /**
   * Adds the tblastn Advanced Options to the passed in form.
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
    $blast = self::TBLASTN;

    // Edit and Resubmit functionality.
    // We want to pull up the details from a previous blast and fill them in
    // as defaults for this blast.
    $options = (isset($blast_cache)) ? $blast_cache : [];
    $defaults = TripalBlastProgramHelper::programGetDefaultValues($options, $blast);

    $form_alter = [];

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
    return [];
  }

}
