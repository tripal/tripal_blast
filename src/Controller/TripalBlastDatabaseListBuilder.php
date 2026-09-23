<?php

namespace Drupal\tripal_blast\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines TripalBlastDatabaseListBuilder class.
 *
 * This is the controller for Tripal BLAST
 * Database List Builder (configuration entity).
 * Lists all BLAST database from the configuration entity.
 */
class TripalBlastDatabaseListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Database ID');
    $header['name'] = $this->t('Name');
    $header['entity_id'] = $this->t('Entity ID');
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
    $row['entity_id'] = $this->entityLink($entity->getEntityId());
    $row['path'] = $entity->getPath();

    $dbtype = $entity->getDbType() == 'n' ? 'Nucleotide (n)' : 'Protein (p)';
    $row['type'] = $dbtype;

    $row['db_regexp'] = $entity->getDbRegExp();
    $row['db_id'] = $entity->getDbId();
    $row['db_linkout_type'] = $entity->getDbLinkout();

    return $row + parent::buildRow($entity);
  }

  /**
   * Generates a link to the referenced entity, if present.
   *
   * @param int|null $entity_id
   *   The entity_id.
   *
   * @return Drupal\Core\Link|string
   *   A link to the entity, or an empty string.
   */
  protected function entityLink(int|null $entity_id): Link|string {
    $link = '';
    if ($entity_id) {
      $url = Url::fromUri('base://bio_data/' . $entity_id);
      $url->setOptions([
        'attributes' => [
          'target' => '_blank',
        ],
      ]);
      $link = Link::fromTextAndUrl($entity_id, $url);
    }
    return $link;
  }

}
