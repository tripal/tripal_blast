<?php

namespace Tests\DatabaseSeeders;

use StatonLab\TripalTestSuite\Database\Seeder;

class BlastDBNodeSeeder extends Seeder {

    /**
     * Seeds the database a test blast database node.
     *
     * @return void
     */
    public function up() {

      // Log in the god user.
      global $user;
      $user = user_load(1);

      $node = new \stdClass();
      if (!isset($node->title)) $node->title = 'Test Blast Database';
      $node->type = 'blastdb';
      node_object_prepare($node);

      $node->language = LANGUAGE_NONE;
      $node->uid = $user->uid;
      $node->status = 1;  // published.
      $node->promote = 0; // not promoted.
      $node->comment = 0; // disabled.

      if (!isset($node->db_name)) $node->db_name = 'Test Blast Database';
      if (!isset($node->db_path)) $node->db_path = '/fake/path/here';
      if (!isset($node->db_dbtype)) $node->db_dbtype = 'nucleotide';
      if (!isset($node->dbxref_linkout_type)) $node->dbxref_linkout_type = 'none';
      if (!isset($node->cvitjs_enabled)) $node->cvitjs_enabled = 0;

      $node = node_submit($node);
      node_save($node);

      // log out the god user.
      $user = drupal_anonymous_user();

      return $node;

    }
}
