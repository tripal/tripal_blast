<?php

namespace Drupal\tripal_blast\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class-based hook implementations for Tripal BLAST.
 */
class TripalBlastHooks {
  use StringTranslationTrait;

  #[Hook('theme')]
  public function theme(array $existing, $type, $theme, $path) {
    // Links rendered as a markup for the template file.
    // Query type + programs route.
    $blast_programs = [
      'blastn' => ['nucleotide', 'nucleotide'],
      'blastx' => ['nucleotide', 'protein'],
      'tblastn' => ['protein', 'nucleotide'],
      'blastp' => ['protein', 'protein'],
    ];
    $route_ui = 'tripal_blast.blast_program';
    $links_ui = [];

    foreach ($blast_programs as $name => $param) {
      [$query, $db] = $param;
      $links_ui['link_' . $name] = Link::createFromRoute(
        $this->t($name),
        $route_ui,
        ['query' => $query, 'db' => $db]
      );
    }

    $context_links = [
      // MISC/ADMIN.
      'link_config' => '#',
      'link_jobs' => '#',
      'link_nodeadd' => '#',
      'link_dbadd' => '#',
      'link_dbfields' => '#',
    ];

    // Recent jobs table: Note this is not BLAST program specific.
    $recent_jobs = \Drupal::service('tripal_blast.job_service')->jobsCreateTable();

    return [
      'theme-tripal-blast-ui' => [
        'variables' => [
          'context_links' => $links_ui,
          'recent_jobs' => $recent_jobs,
        ],
        'template' => 'template-tripal-blast-ui',
      ],
      'theme-tripal-blast-help' => [
        'variables' => ['context_links' => $context_links],
        'template' => 'template-tripal-blast-help',
      ],
      'theme-tripal-blast-message' => [
        'variables' => ['data' => []],
        'template' => 'template-tripal-blast-message',
      ],
      'theme-tripal-blast-report-pending' => [
        'variables' => ['job' => []],
        'template' => 'template-tripal-blast-report-pending',
      ],
      'theme-tripal-blast-show-report' => [
        'variables' => ['report' => []],
        'template' => 'template-tripal-blast-show-report',
      ],
    ];
  }
}
