<?php

namespace Drupal\tripal_blast\Commands;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drush\Commands\DrushCommands;

use Drupal\tripal_blast\Entity\TripalBlastDatabase;
/**
 * Drush commands
 */
class TripalBlastCommands extends DrushCommands {

  /**
   * Migrate 'blastdb' from Drupal 7 to the current site.
   * Make sure the Drupal 7 site is configured as $databases['migrate']['default'] in site/default/settings.php
   *
   * @command tripal_blast:migrate
   * @aliases tbmigrate
   * @usage tripal_blast:migrate
   *   Migrate blastdb from a Drupal 7 site. 
   */
  public function migrate ($options = []) {
    // Create MViews
    print "Retrieving data...\n";
    $connection = Database::getConnection('default', 'migrate');
    $query = $connection->select('node', 'n');
    $query->leftjoin('blastdb', 'b', 'b.nid = n.nid');
    $query->fields('n');
    $query->fields('b');
    $query->condition('type', "blastdb");
    
    $result = $query->execute()->fetchAllAssoc('nid');
    if ($result) {
      $index = 0;
      $skip = 0;
      foreach ($result as $nid => $obj) {
        $name = $obj->name;
        $path = $obj->path;
        $dbtype = $obj->dbtype == 'nucleotide' ? 'n' : 'p';
        $dbxref_id_regex = $obj->dbxref_id_regex;
        $dbxref_db_id = $obj->dbxref_db_id;
        $dbxref_linkout_type = $obj->dbxref_linkout_type;

        $blastdb = TripalBlastDatabase::create([
          'id' => $nid,
          'name' => $name,
          'path' => $path,
          'dbtype' => $dbtype,
          'dbxref_id_regexp' => $dbxref_id_regex,
          'dbxref_db_id' => $dbxref_db_id,
          'dbxref_linkout_type' => $dbxref_linkout_type
        ]);
        
        try {
          $blastdb->save();
          $index ++;
        } catch (\Exception $e) {
          // Error creating the blastdb. (id already exists?)
          $skip ++;
        }
      }
      print "$index blastdb migrated. $skip skipped.\n";
    }
    else {
      $this->output()->writeln("No blastdb found.\n");
    }
    
  }
}