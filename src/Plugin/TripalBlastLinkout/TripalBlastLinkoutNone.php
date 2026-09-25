<?php

namespace Drupal\tripal_blast\Plugin\TripalBlastLinkout;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tripal_blast\Attribute\TripalBlastLinkout;

/**
 * This linkout type simply returns the hit name unchanged.
 */
#[TripalBlastLinkout(
  id: 'None',
  label: new TranslatableMarkup('No Linkout'),
  description: new TranslatableMarkup('Hit name is displayed but no link is provided'),
  weight: -90,
)]
class TripalBlastLinkoutNone extends PluginBase implements TripalBlastLinkoutInterface {

  /**
   * {@inheritDoc}
   */
  public function createLink(\SimpleXMLElement $hit): Link|string {
    return $hit->hit_name;
  }

}
