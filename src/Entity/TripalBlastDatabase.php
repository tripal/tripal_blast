<?php
/**
 * @file
 * Contains class definition of Tripal BLAST DB configuration entity.
 */
namespace Drupal\tripal_blast\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tripal_blast\TripalBlastDatabaseInterface;

#[ConfigEntityType(
  id: 'tripalblastdatabase',
  label: new TranslatableMarkup('Tripal Blast Database'),
  handlers: [
    'list_builder' => 'Drupal\tripal_blast\Controller\TripalBlastDatabaseListBuilder',
    'form' => [
      'add' => 'Drupal\tripal_blast\Form\TripalBlastDatabaseForm',
      'edit' => 'Drupal\tripal_blast\Form\TripalBlastDatabaseForm',
      'delete' => 'Drupal\tripal_blast\Form\TripalBlastDatabaseDeleteForm',
    ],
  ],
  admin_permission: 'administer tripal',
  config_prefix: 'database',
  entity_keys: [
    'id' => 'id',
    'label' => 'name',
    'db_url' => 'db_url',
    'path' => 'path',
    'dbtype' => 'dbtype',
    'db_regexp' => 'db_regexp',
    'db_id' => 'db_id',
    'db_linkout_type' => 'db_linkout_type',
  ],
  config_export: [
    'id',
    'name',
    'db_url',
    'path',
    'dbtype',
    'db_regexp',
    'db_id',
    'db_linkout_type',
  ],
  links: [
    'edit-form' => '/admin/tripal/extension/tripal_blast/configuration/tripalblastdatabase/edit/{tripalblastdatabase}',
    'delete-form' => '/admin/tripal/extension/tripal_blast/configuration/tripalblastdatabase/{tripalblastdatabase}/delete',
  ],
)]
/**
 * Defines a Tripal Blast Database configuration entity.
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
 *   config_prefix = "database",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "db_url" = "db_url",
 *     "path" = "path",
 *     "dbtype" = "dbtype",
 *     "db_regexp" = "db_regexp",
 *     "db_id" = "db_id",
 *     "db_linkout_type" = "db_linkout_type"
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "db_url",
 *     "path",
 *     "dbtype",
 *     "db_regexp",
 *     "db_id",
 *     "db_linkout_type"
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
   * A URL pointing to a descriptive page for the blast database.
   * @var string
   */
  protected $db_url;

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
  protected $db_regexp;

  /**
   * The Database records from this BLAST Database reference.
   * @var integer
   */
  protected $db_id;

  /**
   * Type of linkout to be used for this database reference.
   * @var string
   */
  protected $db_linkout_type;

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
  public function getDbUrl() {
    return $this->db_url;
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
  public function getDbRegExp() {
    return $this->db_regexp;
  }

  /**
   * {@inheritdoc}
   */
  public function getDbId() {
    return $this->db_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getDbLinkout() {
    return $this->db_linkout_type;
  }

}
