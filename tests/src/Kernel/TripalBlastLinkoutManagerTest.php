<?php

namespace Drupal\Tests\tripal_blast\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tripal_blast\TripalBlastLinkoutManager;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * @group TripalBlast
 * @group TripalBlastLinkout
 */
#[Group('tripal-blast')]
#[Group('tripal-blast-linkout')]
#[RunTestsInSeparateProcesses]
class TripalBlastLinkoutManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'tripal',
    'tripal_blast',
    'tripal_chado',
  ];

  /**
   * Tests plugin discovery.
   */
  public function testPluginDiscovery(): void {

    /** @var \Drupal\tripal_blast\TripalBlastLinkoutManager $manager */
    $manager = $this->container->get(
      'plugin.manager.tripal_blast_linkout'
    );

    $definitions = $manager->getDefinitions();

    $this->assertArrayHasKey('None', $definitions, 'Failed to discover "None" plugin');
    $this->assertArrayHasKey('Link', $definitions, 'Failed to discover "Link" plugin');
    $this->assertArrayHasKey('JBrowse', $definitions, 'Failed to discover "JBrowse" plugin');

    $this->assertEquals(-90, $definitions['None']['weight'], 'Correct weight not returned for plugin "None"');
    $this->assertEquals(-80, $definitions['Link']['weight'], 'Correct weight not returned for plugin "Link"');
    $this->assertEquals(-70, $definitions['JBrowse']['weight'], 'Correct weight not returned for plugin "JBrowse"');
  }

  /**
   * Tests plugin instantiation.
   */
  public function testCreateInstance(): void {

    /** @var \Drupal\tripal_blast\TripalBlastLinkoutManager $manager */
    $manager = $this->container->get(
      'plugin.manager.tripal_blast_linkout'
    );

    $plugin = $manager->createInstance('None');

    $this->assertInstanceOf(
      'Drupal\tripal_blast\Plugin\TripalBlastLinkout\TripalBlastLinkoutNone',
      $plugin
    );

  }

}
