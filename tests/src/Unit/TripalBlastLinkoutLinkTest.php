<?php

namespace Drupal\tests\tripal_blast\Unit\Plugin\TripalBlastLinkout;

use Drupal\Core\GeneratedUrl;
use Drupal\Core\Link;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\tripal\Services\TripalLogger;
use Drupal\tripal_blast\Plugin\TripalBlastLinkout\TripalBlastLinkoutLink;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * @coversDefaultClass \Drupal\tripal_blast\Plugin\TripalBlastLinkout\TripalBlastLinkoutJbrowse
 *
 * @group TripalBlast
 * @group TripalBlastLinkout
 */
#[Group('tripal-blast')]
#[Group('tripal-blast-linkout')]
#[RunTestsInSeparateProcesses]
class TripalBlastLinkoutLinkTest extends UnitTestCase {

  /**
   * Setup test environment.
   */
  protected function setUp(): void {
    parent::setUp();

    $url_generator = $this->createMock(UrlGeneratorInterface::class);
    $url_generator
      ->method('generateFromRoute')
      ->willReturn(new GeneratedUrl());

    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
    $container->set('url_generator', $url_generator);

    \Drupal::setContainer($container);
  }

  /**
   * Tests creation of a JBrowse link.
   */
  public function testCreateLink(): void {

    $logger = $this->createMock(TripalLogger::class);

    $plugin = new TripalBlastLinkoutLink(
      [],
      'Link',
      [],
      $logger,
    );

    $hit = new \SimpleXMLElement(
'<hit>
  <hit_name>entity_1234</hit_name>
  <query_name>QueryX</query_name>
  <url_prefix>base://bio_data/{accession}</url_prefix>
  <linkout_id>1234</linkout_id>
</hit>'
    );

    $result = $plugin->createLink($hit);

    $this->assertInstanceOf(Link::class, $result, 'Link is not of correct class');
    $this->assertEquals('1234', (string) $result->getText(), 'Returned link is not the value from the hit xml object');
 }

}
