<?php

namespace Drupal\tests\tripal_blast\Unit\Plugin\TripalBlastLinkout;

use Symfony\Component\DependencyInjection\ContainerBuilder;
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

    $container = new ContainerBuilder();
    $container->set('url_generator', $url_generator);

    \Drupal::setContainer($container);
  }

  /**
   * Tests creation of a JBrowse link.
   */
  public function testCreateLink(): void {

    $messages = [];
    $logger = $this->createMock(TripalLogger::class);
    $logger->method('error')
      ->willReturnCallback(function ($message, array $context = []) use (&$messages) {
        $messages[] = [
          'level' => 'error',
          'message' => $message,
          'context' => $context,
        ];
      }
    );

    $plugin = new TripalBlastLinkoutLink(
      [],
      'Link',
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
    $this->assertCount(0, $messages, 'Did not expect any logger messages');

    // Tests a url prefix with no substitution tokens.
    $hit = new \SimpleXMLElement(
    '<hit>
  <hit_name>entity_1234</hit_name>
  <query_name>QueryX</query_name>
  <url_prefix>base://bio_data/</url_prefix>
  <linkout_id>1234</linkout_id>
  <db>56</db>
</hit>'
    );
    $result = $plugin->createLink($hit);
    $this->assertInstanceOf(Link::class, $result, 'Link is not of correct class');
    // Displayed text.
    $this->assertEquals('1234', (string) $result->getText(), 'Returned link display value is not the value from the hit xml object');
    // Url link.
    $this->assertEquals('base://bio_data/1234', $result->getUrl()->getUri(), 'Returned link url is not the expected value');
    $this->assertCount(0, $messages, 'Did not expect any logger messages');

    // Tests a url prefix with substitution.
    $hit = new \SimpleXMLElement(
    '<hit>
  <hit_name>entity_1234</hit_name>
  <query_name>QueryX</query_name>
  <url_prefix>base://bio_data/{db}/{accession}</url_prefix>
  <linkout_id>1234</linkout_id>
  <db>56</db>
</hit>'
    );
    $result = $plugin->createLink($hit);
    $this->assertInstanceOf(Link::class, $result, 'Link is not of correct class');
    // Displayed text.
    $this->assertEquals('1234', (string) $result->getText(), 'Returned link display value is not the value from the hit xml object');
    // Url link.
    $this->assertEquals('base://bio_data/56/1234', $result->getUrl()->getUri(), 'Returned link url is not the expected value');
    $this->assertCount(0, $messages, 'Did not expect any logger messages');

    // Tests with an invalid urlprefix. Expect logger message.
    $hit = new \SimpleXMLElement(
    '<hit>
  <hit_name>entity_1234</hit_name>
  <query_name>QueryX</query_name>
  <url_prefix>://bio_data/{db}/{accession}</url_prefix>
  <linkout_id>1234</linkout_id>
  <db>56</db>
</hit>'
    );
    $result = $plugin->createLink($hit);
    $this->assertCount(1, $messages, 'Expected a logger message for an invalid url prefix');
    $this->assertStringContainsString('is invalid. You must use a valid URI scheme', $messages[0]['message'], 'Not the expected message');
    $this->assertIsString($result, 'Link should be a string when url prefix is invalid');
    $this->assertEquals('entity_1234', $result, 'Returned link is not the hit name value from the hit xml object');

  }

}
