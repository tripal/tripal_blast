<?php

namespace Drupal\tests\tripal_blast\Unit\Plugin\TripalBlastLinkout;

use Drupal\Core\GeneratedUrl;
use Drupal\Core\Link;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\tripal\Services\TripalLogger;
use Drupal\tripal_blast\Plugin\TripalBlastLinkout\TripalBlastLinkoutJbrowse;
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
class TripalBlastLinkoutJbrowseTest extends UnitTestCase {

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

    $plugin = new TripalBlastLinkoutJbrowse(
      [],
      'JBrowse',
      [],
      $logger,
    );

    $hit = new \SimpleXMLElement(
'<hit>
  <hit_name>subject1</hit_name>
  <query_name>QueryA</query_name>
  <url_prefix>https://jbrowse.example.org/?</url_prefix>
  <linkout_id>chr1</linkout_id>
  <Hit_hsps>
    <Hsp>
      <Hsp_hit-from>100</Hsp_hit-from>
      <Hsp_hit-to>200</Hsp_hit-to>
    </Hsp>
    <Hsp>
      <Hsp_hit-from>300</Hsp_hit-from>
      <Hsp_hit-to>350</Hsp_hit-to>
    </Hsp>
  </Hit_hsps>
</hit>'
    );

    $result = $plugin->createLink($hit);

    $this->assertInstanceOf(Link::class, $result, 'Link is not of correct class');
    $this->assertEquals('chr1', (string) $result->getText(), 'Returned link is not the value from the hit xml object');
 }

}
