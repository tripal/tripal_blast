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
    $header['nid'] = $this->t('Database ID');
    $header['name'] = $this->t('Blast Database Name');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->nid();
    $row['name'] = $entity->name();

    return $row + parent::buildRow($entity);
  }
}