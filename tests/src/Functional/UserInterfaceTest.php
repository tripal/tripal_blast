<?php

namespace Drupal\Tests\tripal_blast\Functional;

use Drupal\Core\Url;
use Drupal\Tests\tripal_chado\Functional\ChadoTestBrowserBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Checks the main user program listing found at SITE/blast.
 */
#[Group('TripalBlast')]
#[Group('User Interface')]
#[RunTestsInSeparateProcesses]
class UserInterfaceTest extends ChadoTestBrowserBase {

  /**
   * The theme to use when rendering content in the test environment.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['tripal', 'tripal_blast'];

  /**
   * Tests that a specific set of pages load with a 200 response.
   */
  public function testLoadUi() {
    $session = $this->getSession();

    // Ensure we have an admin user.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    // Blast UI.
    $this->drupalGet(Url::fromRoute('tripal_blast.blast_ui'));
    $status_code = $session->getStatusCode();
    $this->assertEquals(200, $status_code, "The blast user interface listing the programs available should be able to load.");

  }

}
