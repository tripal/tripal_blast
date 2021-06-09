<?php
/**
 * @file
 * Contains class definition of Tripal BLAST Database Interface.
 */
namespace Drupal\tripal_blast;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining the BLAST database entity.
 */
interface TripalBlastDatabaseInterface extends ConfigEntityInterface {  
  public function getId();
  public function getName();
  public function getPath();
  public function getDbType();
  public function getDbXrefRegExp();
  public function getDbXref();
  public function getDbXrefLinkout();
  public function getCvitjsEnabled();
}