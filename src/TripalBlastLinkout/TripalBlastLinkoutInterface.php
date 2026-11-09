<?php

namespace Drupal\tripal_blast\Plugin\TripalBlastLinkout;

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
   *     - Hit_num:
   *     - Hit_id:
   *     - Hit_def:
   *     - Hit_accession:
   *     - Hit_len:
   *     - Hit_hsps:.
   *
   * @return \Drupal\Core\Link|string
   *   A Link object, otherwise the hit_name string when a linkout is
   *   not possible.
   */
  public function createLink(\SimpleXMLElement $hit): Link|string;

}
