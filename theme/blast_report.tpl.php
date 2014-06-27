<?php

?>

<script type="text/javascript">
  $(document).ready(function(){
    $("#blast_report tr:not(.result-summary)").hide();
    $("#blast_report tr:first-child").show();

    $("#blast_report tr.result-summary").click(function(){
      $(this).next("tr").toggle();
      $(this).find(".arrow").toggleClass("up");
    });
  });
</script>

<?php
//Load the XML file
$path = current_path();
if (preg_match('%blast/report/([\w\.]+)%',$path,$matches)) {
  $filename = 'sites/default/files/' . $matches[1];
  $xml=simplexml_load_file($filename);
}

$header = array(
  'number' =>  array('data' => '#', 'class' => array('number')),
  'query' =>  array('data' => 'Query Name', 'class' => array('query')),
  'hit' =>  array('data' => 'Hit Name', 'class' => array('hit')),
  'evalue' =>  array('data' => 'E-Value', 'class' => array('evalue')),
  'arrow-col' =>  array('data' => '', 'class' => array('arrow-col'))
);

$rows = array();
$count = 0;

foreach($xml->{'BlastOutput_iterations'}->children() as $iteration) {
  foreach($iteration->{'Iteration_hits'}->children() as $hit) {
    if (is_object($hit)) {
      $count +=1;

      $zebra_class = ($count % 2 == 0) ? 'even' : 'odd';

      // SIMPLY SUMMARY ROW
      $hit_name = $hit->Hit_def;
      if (preg_match('/(\w+)/', $hit_name, $matches)) {
        $hit_name = $matches[1];
      }
      $score = $hit->Hit_hsps->Hsp->Hsp_score;
      $evalue = $hit->Hit_hsps->Hsp->Hsp_evalue;
      $query_name = $iteration->{'Iteration_query-def'};

      $row = array(
        'data' => array(
          'number' => array('data' => $count, 'class' => array('number')),
          'query' => array('data' => $query_name, 'class' => array('query')),
          'hit' => array('data' => l($hit_name,''), 'class' => array('hit')),
          'evalue' => array('data' => $evalue, 'class' => array('evalue')),
          'arrow-col' => array('data' => '<div class="arrow"></div>', 'class' => array('arrow-col'))
        ),
        'class' => array('result-summary')
      );
      $rows[] = $row;

      // ALIGNMENT ROW (collapsed by default)
      // Process HSPs
      $HSPs = array();
      foreach ($hit->{'Hit_hsps'}->children() as $hsp_xml) {
        $HSPs[] = (array) $hsp_xml;
      }

      $row = array(
        'data' => array(
          'number' => '',
          'query' => array(
            'data' => theme('blast_report_alignment_row', array('HSPs' => $HSPs)),
            'colspan' => 4,
          )
        ),
        'class' => array('alignment-row', $zebra_class),
        'no_striping' => TRUE
      );
      $rows[] = $row;

    }
  }
}

print theme('table', array(
    'header' => $header,
    'rows' => $rows,
    'attributes' => array('id' => 'blast_report'),
  ));
?>
