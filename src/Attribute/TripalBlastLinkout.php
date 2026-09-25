<?php

namespace Drupal\tripal_blast\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an attribute to mark a class as a TripalBlastLinkout plugin.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class TripalBlastLinkout extends Plugin {

  /**
   * Constructs a TripalLinkout attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   The human-readable name of the plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   A detailed description of the plugin.
   * @param int $weight
   *   Weight to use for sorting plugins into desired order for select.
   */
  public function __construct(
    string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly int $weight = 0,
  ) {
    parent::__construct($id);
  }

}
