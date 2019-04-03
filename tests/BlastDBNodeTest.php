<?php
namespace Tests;

use StatonLab\TripalTestSuite\DBTransaction;
use StatonLab\TripalTestSuite\TripalTestCase;
use Faker\Factory;

/**
 * Tests BlastDB Node CRUD
 */
class BlastDBNodeTest extends TripalTestCase {
  // Uncomment to auto start and rollback db transactions per test method.
  use DBTransaction;

  /**
   * BlastDB Node Type exists.
   *
   * Check that the BlastDB node type exists. It should be created
   * when the module is installed by the Drupal Node API.
   */
  public function testBlastDBNodeExists() {

    // Get a list of all types available.
    $types = node_type_get_types();

    // The BlastDB node type must be in the list.
    $this->assertArrayHasKey('blastdb', $types, '"Blast Database" node type is not registered with Drupal.');

    // Additionally, the blastdb node type should be created by blast_ui.\
    // This checks the case where the node type might be created by
    // a different module.
    $this->assertEquals($types['blastdb']->module, 'blast_ui', '"Blast Database" node type was not added by blast_ui.');
  }

  /**
   * Test Creating a BlastDB Node.
   *
   * Note: We can't test this by submitting the form via PUT because it requires
   *  permission to access /node/add/blastdb; however, we don't yet have a
   *  way to do this with TripalTestSuite. Furthermore, testing HTTP Requests
   *  would not give us access to the data added via the test due to database
   *  transactions.
   */
  public function testBlastDBNodeCreate() {
    module_load_include('inc', 'node', 'node.pages');

    // Log in the god user.
    global $user;
    $user = user_load(1);

    $node = array('type' => 'blastdb');

    // Fill in the form.
    $faker = Factory::create();
    $form_state = array(
      'values' => array(
        'db_name' => $faker->words(3, TRUE),
        'db_path' => '/fake/path/here',
        'db_dbtype' => 'nucleotide',
        'dbxref_linkout_type' => 'none',
        'cvitjs_enabled' => 0,
        'op' => t('Save'),
      ),
    );

    // Execute the node creation form.
    drupal_form_submit('blastdb_node_form', $form_state, (object) $node);

    // Retrieve any errors.
    $errors = form_get_errors();

    // Assert that there must not be any.
    $this->assertEmpty($errors, 'Form submission returned the following errors:'.print_r($errors,TRUE));

    // Check that there is a test blast database.
    $result = db_query('SELECT * FROM {blastdb} WHERE name=:name',
      array(':name' => $form_state['values']['db_name']));
    $this->assertEquals(1, $result->rowCount(), 'Unable to select the blast database using the name.');

    // log out the god user.
    $user = drupal_anonymous_user();
  }

  /**
   * Update an existing Blast Database Node.
   */
  public function testBlastDBNodeUpdate() {
    module_load_include('inc', 'node', 'node.pages');

    // Log in the god user.
    global $user;
    $user = user_load(1);

    // Create the node in the first place.
    $seeder = DatabaseSeeders\BlastDBNodeSeeder::seed();
    $node = $seeder->getNode();

    // Now use the form to edit it :-)
    // Specifically, we will change the name and type.
    $faker = Factory::create();
    $form_state = array(
      'values' => array(
        'db_name' => $faker->words(4, TRUE),
        'db_path' => $node->db_path,
        'db_dbtype' => 'protein',
        'dbxref_linkout_type' => $node->dbxref_linkout_type,
        'cvitjs_enabled' => $node->cvitjs_enabled,
        'op' => t('Save'),
      ),
    );

    // Execute the node creation form.
    drupal_form_submit('blastdb_node_form', $form_state, $node);

    // Retrieve any errors.
    $errors = form_get_errors();

    // Assert that there must not be any.
    $this->assertEmpty($errors, 'Form submission returned the following errors:'.print_r($errors,TRUE));

    // Check that there is a test blast database.
    $result = db_query('SELECT * FROM {blastdb} WHERE name=:name AND dbtype=:type',
      array(':name' => $form_state['values']['db_name'], ':type' => $form_state['values']['db_dbtype']));
    $this->assertEquals(1, $result->rowCount(), 'Unable to select the blast database using the new name and type.');

    // log out the god user.
    $user = drupal_anonymous_user();

  }

  /**
   * Test deleting a node.
   * NOTE: We cannot test this via drupal_form_submit() since it requires a confirmation.
   */
}
