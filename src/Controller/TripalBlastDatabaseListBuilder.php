<?php
/**
 * @file 
 * This is the controller for Tripal BLAST
 * Database List Builder (configuration entity).
 */

namespace Drupal\tripal_blast\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines TripalBlastDatabaseListBuilder class.
 * Lists all BLAST database from configuration entity.
 */
class TripalBlastDatabaseListBuilder extends ConfigEntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Database ID');
    $header['name'] = $this->t('Name');
    $header['path'] = $this->t('Path');
    $header['type'] = $this->t('Type');
    $header['dbxref_id_regexp'] = $this->t('REGEXP Key');
    $header['dbxref_db_id'] = $this->t('DBXref Id');
    $header['dbxref_linkout_type'] = $this->t('Linkout Type');
    $header['cvitjs_enabled'] = $this->t('Enable CvitJS');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->getId();
    $row['name'] = $entity->getName();
    $row['path'] = $entity->getPath();

    $dbtype = $entity->getDbType() == 'n' ? 'Nucleotide (n)' : 'Protein (p)';
    $row['type'] = $dbtype;

    $row['dbxref_id_regexp'] = $entity->getDbXrefRegExp();
    $row['dbxref_db_id'] = $entity->getDbXref();
    $row['dbxref_linkout_type'] = $entity->getDbXrefLinkout();

    $enabled = $entity->getCvitjsEnabled() ? 'Enabled' : 'Disabled';
    $row['cvitjs_enabled'] = $enabled;

    return $row + parent::buildRow($entity);
  }
}