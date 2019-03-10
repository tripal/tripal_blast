<?php
namespace Tests\api;

use StatonLab\TripalTestSuite\DBTransaction;
use StatonLab\TripalTestSuite\TripalTestCase;

class BlastDBApiTest extends TripalTestCase {
  // Uncomment to auto start and rollback db transactions per test method.
  use DBTransaction;

  /**
   * Tests get_blast_database().
   */
  public function testGetBlastDB() {

    // Create a node to fetch.
    $seeder = \Tests\DatabaseSeeders\BlastDBNodeSeeder::seed(); 
    $node = $seeder->getNode();
    
    // Using the nid?
    $resultdb = get_blast_database(['nid' => $node->nid]);
    $this->assertEquals($node->nid, $resultdb->nid,
      "Unable to find the correct blast database based on nid.");

    // Using the name.
    $resultdb = get_blast_database(['name' => $node->db_name]);
    $this->assertEquals($node->nid, $resultdb->nid,
      "Unable to find the correct blast database based on name.");

    // Using the path.
    $resultdb = get_blast_database(['path' => $node->db_path]);
    $this->assertEquals($node->nid, $resultdb->nid,
      "Unable to find the correct blast database based on path.");
  }

  /**
   * Tests get_blast_database_options().
   * @todo test for protein as well.
   * @todo test with permissions.
   */
  public function testGetBlastDBOptions() {

    // Create 3 nodes to fetch.
    $nodes = array();
    $seeder = \Tests\DatabaseSeeders\BlastDBNodeSeeder::seed(); 
    $nodes[] = $seeder->getNode();
    $seeder = \Tests\DatabaseSeeders\BlastDBNodeSeeder::seed(); 
    $nodes[] = $seeder->getNode(); 
    $seeder = \Tests\DatabaseSeeders\BlastDBNodeSeeder::seed(); 
    $nodes[] = $seeder->getNode();

    $options = get_blast_database_options('nucleotide');

    $this->assertGreaterThanOrEqual(3, sizeof($options),
      "Did not retrieve all 3 nodes we inserted as options.");

    // Check each node we inserted is in the options.
    foreach ($nodes as $node) {
      $this->assertArrayHasKey($node->nid, $options,
        "Unable to find a specific node option that we know should be there.");
    }

    // Also check get_blast_database_nodes() directly.
    $retrieved_nodes = get_blast_database_nodes();
    $this->assertGreaterThanOrEqual(3, sizeof($retrieved_nodes),
      "Unable to retrieve the nodes at all.");
    foreach ($nodes as $node) {
      $this->assertArrayHasKey($node->nid, $retrieved_nodes,
        "Unable to find a specific node option that we know should be there.");
    }

  }
}
