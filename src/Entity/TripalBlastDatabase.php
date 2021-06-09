<?php
/**
 * @file
 * Contains class definition of Tripal BLAST DB configuration entity.
 */
namespace Drupal\tripal_blast\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\tripal_blast\TripalBlastDatabaseInterface;

/**
 * Defines an image style configuration entity.
 *
 * @ConfigEntityType(
 *   id = "tripalblastdatabase",
 *   label = @Translation("Tripal Blast Database"),
 *   handlers = {
 *     "list_builder" = "Drupal\tripal_blast\Controller\TripalBlastDatabaseListBuilder",
 *     "form" = {
 *       "add" = "Drupal\tripal_blast\Form\TripalBlastDatabaseForm",
 *       "edit" = "Drupal\tripal_blast\Form\TripalBlastDatabaseForm",
 *       "delete" = "Drupal\tripal_blast\Form\TripalBlastDatabaseDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer tripal",
 *   config_prefix = "tripal_blast",
 *   entity_keys = {
 *     "id" = "id",
 *     "name" = "name",
 *     "path" = "path",
 *     "dbtype" = "dbtype",
 *     "dbxref_id_regexp" = "dbxref_id_regexp",
 *     "dbxref_db_id" = "dbxref_db_id",
 *     "dbxref_linkout_type" = "dbxref_linkout_type",
 *     "cvitjs_enabled" = "cvitjs_enabled"
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "path",
 *     "dbtype",
 *     "dbxref_id_regexp",
 *     "dbxref_db_id",
 *     "dbxref_linkout_type",
 *     "cvitjs_enabled"
 *   },
 *   links = {
 *     "edit-form" = "/admin/tripal/extension/tripal_blast/configuration/tripalblastdatabase/edit/{tripalblastdatabase}",
 *     "delete-form" = "/admin/tripal/extension/tripal_blast/configuration/tripalblastdatabase/{tripalblastdatabase}/delete"
 *   }
 * )
 */
class TripalBlastDatabase extends ConfigEntityBase implements TripalBlastDatabaseInterface {  
  /**
   * The primary identifier for a node.
   * @var integer
   */
  protected $id;

  /**
   * The human-readable name of the blast database.
   * @var string
   */
  protected $name;

  /**
   * The full path and filename prefix of the blast database.
   * @var string
   */
  protected $path;

  /**
   * Type of the blast database. Should be either n for nucleotide or p for protein.
   * @var string
   */
  protected $dbtype;

  /**
   * The Regular Expression to use to extract the id from the FASTA header of the BLAST database hit.
   * @var string
   */
  protected $dbxref_id_regexp;

  /**
   * The Database records from this BLAST Database reference.
   * @var integer
   */
  protected $dbxref_db_id;

  /**
   * Type of linkout to be used for this database reference.
   * @var string
   */
  protected $dbxref_linkout_type;

  /**
   * Indicate if CViTjs should be used to display hits on a whole genome.
   * @var boolean
   */
  protected $cvitjs_enabled;

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function getDbType() {
    return $this->dbtype;
  }

  /**
   * {@inheritdoc}
   */
  public function getDbXrefRegExp() {
    return $this->dbxref_id_regexp;
  }

  /**
   * {@inheritdoc}
   */
  public function getDbXref() {
    return $this->dbxref_db_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getDbXrefLinkout() {
    return $this->dbxref_linkout_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getCvitjsEnabled() {
    return $this->cvitjs_enabled;
  }
}