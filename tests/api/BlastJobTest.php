<?php
namespace Tests;

use StatonLab\TripalTestSuite\DBTransaction;
use StatonLab\TripalTestSuite\TripalTestCase;
use Faker\Factory;

/**
 * Tests running BLAST jobs. Specifically, run_BLAST_tripal_job().
 */
class BlastJobTest extends TripalTestCase {
  use DBTransaction;

  /**
   * Tests BLASTN
   */
  public function testBLASTn() {
    $faker = Factory::create();

    // Test we have access to the NCBI Blast commands.
    // Setting the default to where it is on Travis CI: /usr/local/bin.
    $blast_path = variable_get('blast_path', '/usr/local/bin/');
    $this->assertFileExists($blast_path . 'blastn', 'NCBI blastn command not found. Expecting it here: '.$blast_path.'blastn');
    $this->assertFileExists($blast_path . 'blast_formatter', 'NCBI blast_formatter command not found. Expecting it here: '.$blast_path.'blast_formatter');

    // Make sure the path to blast is set!
    if ($blast_path == '/usr/local/bin/') {
      variable_set('blast_path', '/usr/local/bin/');
    }

    // Retrieve paths to files.
    $module_path = DRUPAL_ROOT . '/' . drupal_get_path('module','blast_ui');
    $file_path = DRUPAL_ROOT . '/' . variable_get('file_public_path', conf_path() . '/files'); 

    // Set parameters for run_BLAST_tripal_job().
    $program = 'blastn';
    $query = $module_path . '/tests/test_files/Citrus_sinensis-orange1.1g015632m.g.fasta';
    $database = $module_path . '/tests/test_files/Citrus_sinensis-scaffold00001';
    $output_filestub = $file_path . '/tripal/tripal_blast/' . $faker->word;

    // Quick check that the output file doesnt already exists ;-).
    $this->assertFileNotExists($output_filestub . '.asn', "Result File, $output_file, already exists before the command is run.");

    // We start the test with no options.
    $options = array();

    // Supress output and tripal errors.
    // NOTE: silent() didn't work for some reason.
    putenv("TRIPAL_SUPPRESS_ERRORS=TRUE");
    ob_start();

    run_BLAST_tripal_job($program, $query, $database, $output_filestub, $options);

    // Clean the buffer and unset tripal errors suppression. 
    ob_end_clean();
    putenv("TRIPAL_SUPPRESS_ERRORS");

    // Loop through each expected output file...
    $files_to_check = array();
    $files_to_check[] = $output_filestub . '.asn';
    $files_to_check[] = $output_filestub . '.xml';
    $files_to_check[] = $output_filestub . '.tsv';
    $files_to_check[] = $output_filestub . '.html';
    $files_to_check[] = $output_filestub . '.gff';
    foreach($files_to_check as $output_file) {

      // Check that the file exists.
      $this->assertFileExists($output_file, "Result File, $output_file, doesn't exist.");

      // Check that the file is not empty.
      $this->assertNotEquals(0, filesize($output_file), "The Result File, $output_file, is empty.");

      // Clean-up by removing the file.
      unlink($output_file);
    }

  }
}
