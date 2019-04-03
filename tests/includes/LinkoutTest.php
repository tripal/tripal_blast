<?php
namespace Tests\includes;

use StatonLab\TripalTestSuite\DBTransaction;
use StatonLab\TripalTestSuite\TripalTestCase;

class LinkoutTest extends TripalTestCase {
  // Uncomment to auto start and rollback db transactions per test method.
  use DBTransaction;

  /**
   * Test tripal_blast_generate_linkout_link().
   *
   * @dataProvider provideBlastHit
   */
  public function testGeneralLinkout($hit) {

    // Test with no query parameters.
    $url_prefix = 'http://fake/url/prefix';
    $info = [
      'query_name' => 'Fred',
      'score' => 33,
      'e-value' => '3.4e-55',
    ];
    $hit->{'linkout_id'} = 'Sarah';
    $result = tripal_blast_generate_linkout_link($url_prefix, $hit, $info);

    $expect = '<a href="http://fake/url/prefixSarah" target="_blank">Sarah</a>';
    $this->assertEquals($expect, $result);

    // Check with Query paramters.
    $url_prefix = 'http://fake/url/prefix?me=you&he=her&hit=';
    $result = tripal_blast_generate_linkout_link($url_prefix, $hit, $info);

    $expect = '<a href="http://fake/url/prefix?me=you&amp;he=her&amp;hit=Sarah" target="_blank">Sarah</a>';
    $this->assertEquals($expect, $result);
  }

  /**
   * Test tripal_blast_generate_linkout_gbrowse().
   *
   * @dataProvider provideBlastHit
   */
  public function testGBrowseLinkout($hit) {

    // Process HSPs for $info.
    foreach ($hit->{'Hit_hsps'}->children() as $hsp_xml) {
      $HSPs[] = (array) $hsp_xml;
    }

    // Test with no query parameters.
    $url_prefix = 'http://fake/url/prefix';
    $info = [
      'query_name' => 'Fred',
      'score' => 33,
      'e-value' => '3.4e-55',
      'HSPs' => $HSPs,
    ];
    $hit->{'linkout_id'} = 'Sarah';
    $result = tripal_blast_generate_linkout_gbrowse($url_prefix, $hit, $info);

    $expect = '<a href="http://fake/url/prefix?ref=Sarah;&amp;start=1684180;&amp;stop=4062210;&amp;add=Sarah%20BLAST%20BlastHit%204058460..4058900%2C4061822..4062210%2C4059787..4060062%2C4059171..4059331%2C4060624..4060765%2C4061617..4061719%2C4061248..4061345%2C4059420..4059514%2C4061415..4061500%2C1684180..1684365%2C4060282..4060361%2C4059598..4059660%2C4060479..4060539%2C4059018..4059076%2C4060854..4060909%2C1686050..1686133%2C1685333..1685464%2C1685160..1685217%2C2213219..2213247;&amp;h_feat=BlastHit" target="_blank">Sarah</a>';
    $this->assertEquals($expect, $result);

  }

  /**
   * Test tripal_blast_generate_linkout_jbrowse().
   *
   * @dataProvider provideBlastHit
   */
  public function testJBrowseLinkout($hit) {

    // Proccess the HSPs for $info.
    foreach ($hit->{'Hit_hsps'}->children() as $hsp_xml) {
      $HSPs[] = (array) $hsp_xml;
    }

    // Test with no query parameters.
    $url_prefix = 'http://myserver.com/jbrowse/databasica/?tracks=myfavtrack,anoktrack,blast&';
    $info = [
      'query_name' => 'Fred',
      'score' => 33,
      'e-value' => '3.4e-55',
      'HSPs' => $HSPs,
    ];
    $hit->{'linkout_id'} = 'Sarah';
    $hit->{'hit_name'} = 'Needleman';
    $result = tripal_blast_generate_linkout_jbrowse($url_prefix, $hit, $info);

    $expect = '<a href="http://myserver.com/jbrowse/databasica/?tracks=myfavtrack,anoktrack,blast&amp;loc=Sarah:1287842..4458548&amp;addFeatures=[{&quot;seq_id&quot;:&quot;Sarah&quot;,&quot;start&quot;:1684180,&quot;end&quot;:4062210,&quot;name&quot;:&quot;Fred Blast Hit&quot;,&quot;strand&quot;:1,&quot;subfeatures&quot;:[{&quot;start&quot;:4058460,&quot;end&quot;:4058900,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:4061822,&quot;end&quot;:4062210,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:4059787,&quot;end&quot;:4060062,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:4059171,&quot;end&quot;:4059331,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:4060624,&quot;end&quot;:4060765,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:4061617,&quot;end&quot;:4061719,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:4061248,&quot;end&quot;:4061345,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:4059420,&quot;end&quot;:4059514,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:4061415,&quot;end&quot;:4061500,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:1684180,&quot;end&quot;:1684365,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:4060282,&quot;end&quot;:4060361,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:4059598,&quot;end&quot;:4059660,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:4060479,&quot;end&quot;:4060539,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:4059018,&quot;end&quot;:4059076,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:4060854,&quot;end&quot;:4060909,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:1686050,&quot;end&quot;:1686133,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:1685333,&quot;end&quot;:1685464,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:1685160,&quot;end&quot;:1685217,&quot;strand&quot;:&quot;1&quot;,&quot;type&quot;:&quot;match_part&quot;},{&quot;start&quot;:2213219,&quot;end&quot;:2213247,&quot;strand&quot;:&quot;-1&quot;,&quot;type&quot;:&quot;match_part&quot;}]}]&amp;addTracks=[{&quot;label&quot;:&quot;blast&quot;,&quot;key&quot;:&quot;BLAST Result&quot;,&quot;type&quot;:&quot;JBrowse/View/Track/CanvasFeatures&quot;,&quot;store&quot;:&quot;url&quot;}]" target="_blank">Sarah</a>';
    $this->assertEquals($expect, $result);

  }

  /**
   * Data Provider
   */
  public function provideBlastHit() {

    $result = simplexml_load_file(
      DRUPAL_ROOT .'/'. drupal_get_path('module','blast_ui')
      .'/tests/test_files/Citrus_sinensis-orange1.1g015632m.blastresults.xml'
    );
    $hit = $result->{'BlastOutput_iterations'}->{'Iteration'}[0]->{'Iteration_hits'}->{'Hit'};
    
    return [[$hit]];
  }
}
