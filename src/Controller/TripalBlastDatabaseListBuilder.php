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
    $header['db_regexp'] = $this->t('REGEXP Key');
    $header['db_id'] = $this->t('External Database');
    $header['db_linkout_type'] = $this->t('Linkout Type');

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

    $row['db_regexp'] = $entity->getDbRegExp();
    $row['db_id'] = $entity->getDbId();
    $row['db_linkout_type'] = $entity->getDbLinkout();

    return $row + parent::buildRow($entity);
  }
}
