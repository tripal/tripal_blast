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

  public function getEntityId();

  public function getPath();

  public function getDbType();

  public function getDbRegExp();

  public function getDbId();

  public function getDbLinkout();

}
