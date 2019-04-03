<?php
namespace Tests\api;

use StatonLab\TripalTestSuite\DBTransaction;
use StatonLab\TripalTestSuite\TripalTestCase;

class FilesTest extends TripalTestCase {
  // Uncomment to auto start and rollback db transactions per test method.
  use DBTransaction;

  /**
   * Tests convert_tsv2gff3().
   */
  public function testTSV2GFF3() {

    // Grab the paths for `
    $tsv_file = DRUPAL_ROOT . '/' . drupal_get_path('module','blast_ui') . '/tests/test_files/Citrus_sinensis-orange1.1g015632m.blastresults.tsv';
    $gff_file = DRUPAL_ROOT . '/' . drupal_get_path('module','blast_ui') . '/tests/test_files/Citrus_sinensis-orange1.1g015632m.blastresults.gff';

    $result_gff = file_directory_temp() . '/' . uniqid() . '.gff';
    convert_tsv2gff3($tsv_file, $result_gff);

    $this->assertFileExists($result_gff,
      "Unable to find resulting GFF3 file.");
    $this->assertEquals(file_get_contents($gff_file), file_get_contents($result_gff),
      "GFF files produced did not contain the expected results.");
  }
}
