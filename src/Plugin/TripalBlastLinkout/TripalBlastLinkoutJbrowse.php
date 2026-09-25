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
 * This linkout type creates a link to JBrowse showing HSPs.
 */
#[TripalBlastLinkout(
  id: 'JBrowse',
  label: new TranslatableMarkup('JBrowse Link'),
  description: new TranslatableMarkup('Provides a link to a JBrowse instance to visualize HSPs'),
  weight: -70,
)]
class TripalBlastLinkoutJbrowse extends PluginBase implements TripalBlastLinkoutInterface, ContainerFactoryPluginInterface {

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

    if (!isset($hit->url_prefix) || !isset($hit->linkout_id)) {
      return $link;
    }

    // First we need to collect the HSPs to define the ranges we want to
    // display on the JBrowse.
    $ranges = [];
    // We also keep track of all the coordinates in order to later
    // calculate the smallest and largest coordinate.
    $coords = [];
    $count = 0;
    $strands = [];
    foreach ($hit->Hit_hsps->Hsp as $hsp) {
      $count++;

      $strand = '1';
      $hsp_start = (int) $hsp->{'Hsp_hit-from'};
      $hsp_end = (int) $hsp->{'Hsp_hit-to'};
      $strands[] = $strand;

      // Handle alignments on the negative strand.
      if (($hsp_end - $hsp_start) < 0) {
        $strand = '-1';
        $hsp_start = (int) $hsp->{'Hsp_hit-to'};
        $hsp_end = (int) $hsp->{'Hsp_hit-from'};
      }

      // Add both the start & stop to the coordinate list.
      $coords[] = (int) $hsp->{'Hsp_hit-from'};
      $coords[] = (int) $hsp->{'Hsp_hit-to'};

      // Format the hsp for inclusion in the subfeatures section of the
      // track later.
      $hsp_def = '{"start":' . $hsp_start . ',"end":' . $hsp_end . ',"strand":"' . $strand . '","type":"match_part"}';
      $ranges[] = $hsp_def;
    }

    // Calculate the minimum & maximum coordinates.
    $min = min($coords);
    $max = max($coords);

    // We also want some white-space on either side of out hit
    // when we show it in the JBrowse. To make this generic,
    // we want our blast hit to take up 2/3 of the screen thus
    // we have 1/6 per side for white-space.
    $buffer = round(($max - $min) / 6);
    $screen_start = $min - $buffer;
    $screen_end = $max + $buffer;

    // Now we are finally ready to build the URL.
    // First lets set the location of the hit so the JBrowse focuses
    // in on the correct region.
    $jbrowse_query = [];
    $jbrowse_query['loc'] = 'loc=' . $hit->linkout_id . ':' . $screen_start . '..' . $screen_end;

    $unique_strands = array_unique($strands);
    if (count($unique_strands) === 1) {
      $strand = end($strands);
      // Next we want to add our BLAST hit to the JBrowse.
      $jbrowse_query['addFeatures'] =
        'addFeatures=[{"seq_id":"' . $hit->linkout_id . '","start":' . $min . ',"end":' . $max
        . ',"name":"' . $hit->query_name . ' Blast Hit","strand":' . $strand . ',"subfeatures":['
        . implode(',', $ranges) . ']}]';
    }
    else {
      $jbrowse_query['addFeatures'] =
        'addFeatures=[{"seq_id":"' . $hit->linkout_id . '","start":' . $min . ',"end":' . $max
        . ',"name":"' . $hit->query_name . ' Blast Hit","subfeatures":['
        . implode(',', $ranges) . ']}]';
    }

    // Then add a track to display our new feature.
    $jbrowse_query['addTracks'] = 'addTracks=[{"label":"blast","key":"BLAST Result","type":"JBrowse/View/Track/HTMLFeatures","store":"url"}]';

    $url_postfix = implode('&', $jbrowse_query);

    $hit_url = $hit->url_prefix . $url_postfix;

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

    return $link;
  }

}
