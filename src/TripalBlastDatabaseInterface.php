<?php

namespace Drupal\tripal_blast;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining the BLAST database entity.
 */
interface TripalBlastDatabaseInterface extends ConfigEntityInterface {

  /**
   * Return the primary identifier for a node.
   */
  public function getId();

  /**
   * Return the human-readable name of the blast database.
   */
  public function getName();

  /**
   * Return a URL pointing to a descriptive page for the blast database.
   */
  public function getDbUrl();

  /**
   * Return the full path and filename prefix of the blast database.
   */
  public function getPath();

  /**
   * Return type of the blast database, n for nucleotide or p for protein.
   */
  public function getDbType();

  /**
   * Return the Regular Expression to use to extract the id from a BLAST hit.
   */
  public function getDbRegExp();

  /**
   * Return the Database records from this BLAST Database reference.
   */
  public function getDbId();

  /**
   * Return type of linkout to be used for this database reference.
   */
  public function getDbLinkout();

}
