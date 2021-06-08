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
 *       "add" = "Drupal\tripal_blast\Form\TripalBlastDatabaseForm"
 *     }
 *   },
 *   admin_permission = "administer tripal",
 *   config_prefix = "tripal_blast",
 *   entity_keys = {
 *     "id" = "nid",
 *   },
 *   config_export = {
 *     "nid",
 *     "name",
 *   }
 * )
 */
class TripalBlastDatabase extends ConfigEntityBase implements TripalBlastDatabaseInterface {  
  /**
   * The primary identifier for a node.
   * @var integer
   */
  protected $nid;

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
  public function getNid() {
    return $this->nid;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }
}