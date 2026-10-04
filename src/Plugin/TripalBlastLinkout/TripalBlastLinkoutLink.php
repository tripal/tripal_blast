<?php

namespace Drupal\tripal_blast\Plugin\TripalBlastLinkout;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\tripal\Services\TripalLogger;
use Drupal\tripal_blast\Attribute\TripalBlastLinkout;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This linkout type uses the url prefix + accession to create the link.
 */
#[TripalBlastLinkout(
  id: 'Link',
  label: new TranslatableMarkup('Basic Link'),
  description: new TranslatableMarkup('Provides a link based on the url prefix + subject'),
  weight: -80,
)]
class TripalBlastLinkoutLink extends PluginBase implements TripalBlastLinkoutInterface, ContainerFactoryPluginInterface {

  /**
   * The Tripal logger service.
   *
   * @var Drupal\tripal\Services\TripalLogger
   */
  protected TripalLogger $tripal_logger;

  /**
   * Constructs a TripalBlastLinkoutLink object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TripalLogger $tripal_logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tripal_logger = $tripal_logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tripal.logger')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function createLink(\SimpleXMLElement $hit): Link|string {
    // Fallback if link cannot be created is just the hit name.
    $link = $hit->hit_name;

    if (isset($hit->url_prefix) && isset($hit->linkout_id)) {
      // Substitute placeholders if present, otherwise append.
      if (str_contains($hit->url_prefix, '{accession}')) {
        $hit_url = preg_replace('/\{accession\}/', $hit->linkout_id, $hit->url_prefix);
      }
      else {
        $hit_url = $hit->url_prefix . $hit->linkout_id;
      }
      if (str_contains($hit->url_prefix, '{db}')) {
        if (isset($hit->db)) {
          $hit_url = preg_replace('/\{db\}/', $hit->db, $hit_url);
        }
      }

      // Create a Link object.
      try {
        $url = Url::fromUri($hit_url);
        $url->setOptions([
          'attributes' => [
            'target' => '_blank',
          ],
        ]);
        $link = Link::fromTextAndUrl($hit->linkout_id, $url);
      }
      catch (\Exception $e) {
        // A failed link situation should not be shown to end-users, because
        // they can't do anything about it, but the site admin should see it,
        // so we need to just log this.
        $this->tripal_logger->error('TripalBlastLinkout "' . $this->getPluginId() . '" error: ' . $e->getMessage());
      }
    }
    return $link;
  }

}
