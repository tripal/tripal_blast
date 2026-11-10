<?php

namespace Drupal\tripal_blast\TripalBlastLinkout;

use Drupal\Core\Link;

/**
 * The interface for creating Blast Linkouts.
 */
interface TripalBlastLinkoutInterface {

  /**
   * Creates a Link object when possible, or returns hit name.
   *
   * Generally this link is specific for the hit sequence, and may be
   * based on an internal or external URL.
   *
   * @param \SimpleXMLElement $hit
   *   The blast XML hit object. This object has the following keys, but
   *   not all plugins will use all keys:
   *     - hit_name: The displayed hit subject name. Required for all plugins.
   *     - url_prefix: Value from the chado.db table urlprefix column.
   *     - linkout_id: The value to add or substitute into the url_prefix.
   *     - Hit_num: The dynamic sequential rank of the database hit,
   *       ordered by statistical relevance. Starts at 1.
   *     - Hit_id: The comprehensive string database identifier of the
   *       matching reference sequence.
   *     - Hit_def: The text definition or descriptive name of the matching
   *       database sequence.
   *     - Hit_accession: The distinct alphanumeric accession string of the
   *       matched database sequence.
   *     - Hit_len: The total sequence length of the matched database record.
   *     - Hit_hsps: High-Scoring Segment Pairs. A single database hit may
   *       contain multiple localized sub-alignments. These are stored in an
   *       array under this key.
   *
   * @return \Drupal\Core\Link|string
   *   A Link object, otherwise the hit_name string when a linkout is
   *   not possible.
   */
  public function createLink(\SimpleXMLElement $hit): Link|string;

}
