<?php

# https://www.berramou.com/blog/drupal-create-custom-plugin-type
namespace Drupal\tripal_blast;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\tripal_blast\Attribute\TripalBlastLinkout;
use Drupal\tripal_blast\Plugin\TripalBlastLinkout\TripalBlastLinkoutInterface;

/**
 * Manages Tripal Blast Linkout plugins.
 */
class TripalBlastLinkoutManager extends DefaultPluginManager {

  /**
   * Constructs a new tripal_blast linkout plugin manager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct(
      'Plugin/TripalBlastLinkout',
      $namespaces,
      $module_handler,
      TripalBlastLinkoutInterface::class,
      TripalBlastLinkout::class,
    );
    $this->alterInfo('tripal_blast_linkout_info');
    $this->setCacheBackend($cache_backend, 'tripal_blast_linkout_plugins');
  }

}
