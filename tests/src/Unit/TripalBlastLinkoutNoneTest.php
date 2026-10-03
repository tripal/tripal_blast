<?php

namespace Drupal\Tests\tripal_blast\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\tripal_blast\Plugin\TripalBlastLinkout\TripalBlastLinkoutNone;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * @coversDefaultClass \Drupal\tripal_blast\Plugin\TripalBlastLinkout\TripalBlastLinkoutNone
 *
 * @group TripalBlast
 * @group TripalBlastLinkout
 */
#[Group('tripal-blast')]
#[Group('tripal-blast-linkout')]
#[RunTestsInSeparateProcesses]
class TripalBlastLinkoutNoneTest extends UnitTestCase {

  /**
   * Tests createLink().
   */
  public function testCreateLinkNone(): void {
    $plugin = new TripalBlastLinkoutNone([], 'None', []);
    $hit = new \SimpleXMLElement('<hit><hit_name>Test Hit</hit_name></hit>');
    $result = $plugin->createLink($hit);
    $this->assertSame('Test Hit', (string) $result, 'Returned link is not the value from the hit xml object');
  }

}
