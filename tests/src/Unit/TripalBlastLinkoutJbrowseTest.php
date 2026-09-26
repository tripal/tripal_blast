<?php

namespace Drupal\tests\tripal_blast\Unit\Plugin\TripalBlastLinkout;

use Symfony\Component\DependencyInjection\ContainerBuilder;
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

    $container = new ContainerBuilder();
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

    // Tests with no url prefix available.
    $hit = new \SimpleXMLElement(
    '<hit>
  <hit_name>entity_1234</hit_name>
  <query_name>QueryX</query_name>
</hit>'
    );
    $result = $plugin->createLink($hit);
    $this->assertIsString($result, 'Link should be a string when no url prefix supplied');
    $this->assertEquals('entity_1234', $result, 'Returned link is not the hit name value from the hit xml object');

    // Tests constructing a JBrowse link on plus strand.
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
    // Displayed text.
    $this->assertEquals('chr1', (string) $result->getText(), 'Returned link display value is not the value from the hit xml object');
    // Url link.
    $this->assertEquals('https://jbrowse.example.org/?loc=chr1:58..392&addFeatures=[{"seq_id":"chr1","start":100,"end":350,"name":"QueryA Blast Hit","strand":1,"subfeatures":[{"start":100,"end":200,"strand":"1","type":"match_part"},{"start":300,"end":350,"strand":"1","type":"match_part"}]}]&addTracks=[{"label":"blast","key":"BLAST Result","type":"JBrowse/View/Track/HTMLFeatures","store":"url"}]',
      $result->getUrl()->getUri(), 'Returned link url is not the expected value');

    // Tests constructing a JBrowse link on minus strand.
    $hit = new \SimpleXMLElement(
    '<hit>
  <hit_name>subject1</hit_name>
  <query_name>QueryB</query_name>
  <url_prefix>https://jbrowse.example.org/?</url_prefix>
  <linkout_id>chr2</linkout_id>
  <Hit_hsps>
    <Hsp>
      <Hsp_hit-from>800</Hsp_hit-from>
      <Hsp_hit-to>700</Hsp_hit-to>
    </Hsp>
    <Hsp>
      <Hsp_hit-from>500</Hsp_hit-from>
      <Hsp_hit-to>450</Hsp_hit-to>
    </Hsp>
  </Hit_hsps>
</hit>'
    );
    $result = $plugin->createLink($hit);
    $this->assertInstanceOf(Link::class, $result, 'Link is not of correct class');
    // Displayed text.
    $this->assertEquals('chr2', (string) $result->getText(), 'Returned link display value is not the value from the hit xml object');
    // Url link.
    $this->assertEquals('ttps://jbrowse.example.org/?loc=chr2:392..858&addFeatures=[{"seq_id":"chr2","start":450,"end":800,"name":"QueryB Blast Hit","strand":1,"subfeatures":[{"start":700,"end":800,"strand":"-1","type":"match_part"},{"start":450,"end":500,"strand":"-1","type":"match_part"}]}]&addTracks=[{"label":"blast","key":"BLAST Result","type":"JBrowse/View/Track/HTMLFeatures","store":"url"}]',
      $result->getUrl()->getUri(), 'Returned link url is not the expected value');

  }

}
