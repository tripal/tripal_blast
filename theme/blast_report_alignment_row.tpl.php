<?php
/**
 * This Template generates the HTML for a single Alignment row in a BLAST report
 *
 * Variables Available in this template:
 *   $HSPs: an array of HSPs for the current BLAST result. This follows the structure
 *     layed out in the XML file but has been made an array instead of a SimpleXML object
 *     for ease of processing and abstration.
 */
?>

<div class="alignment-row-section hit-visualization" title="Your query sequence is shown at the bottom and the target sequence it aligned to is shown at the top. The shape connecting them indicates how much of the target and query are represented by the hit.">
  <div class="title">Hit Visualization</div>
  <img src="data:image/png;base64,<?php print $hit_visualization;?>"/>
  <p>The image above shows the relationship between query and target for this
    particular BLAST hit.</p>
</div>
<div class="alignment-row-section alignment">
  <div class="title">Alignment</div>

  <?php
    foreach($HSPs as $hsp) {
  ?>

    <div class="hsp-title">HSP <?php print $hsp['Hsp_num']?></div>
    <div class="alignment-metrics">
      <span class="identity">
        Identity=&nbsp;
        <?php print $hsp['Hsp_identity']; ?>/<?php print $hsp['Hsp_align-len']; ?> (<?php print round($hsp['Hsp_identity']/$hsp['Hsp_align-len']*100, 2, PHP_ROUND_HALF_EVEN);?>%)
      </span>,&nbsp;
      <span class="positive">
        Positive=&nbsp;
        <?php print $hsp['Hsp_positive']; ?>/<?php print $hsp['Hsp_align-len']; ?> (<?php print round($hsp['Hsp_positive']/$hsp['Hsp_align-len']*100, 2, PHP_ROUND_HALF_EVEN);?>%)
      </span>
      <span class="coord-summary">
        Query Matches <?php print $hsp['Hsp_query-from'] . ' to ' . $hsp['Hsp_query-to']; ?>
        Hit Matches = <?php print $hsp['Hsp_hit-from'] . ' to ' . $hsp['Hsp_hit-to']; ?>
      </span>
    </div>
    <div class="alignment">
      <div class="alignment-row">
        <?php
        // We want to display the alignment with a max 60 residues per line with line numbers indicated.
        // First break up the strings.
        $query = str_split($hsp['Hsp_qseq'], 60);
        $matches = str_split($hsp['Hsp_midline'], 60);
        $hit = str_split($hsp['Hsp_hseq'], 60);
        // determine the max length of the coordinate string to use when padding.
        $coord_length = strlen($hsp['Hsp_hit-from']) + 3;
        $coord_length = (strlen($hsp['Hsp_query-to']) + 3 > $coord_length) ? strlen($hsp['Hsp_query-to']) + 3 : $coord_length;

        // We need to take into account that 3 nucleotides encode 1 amino acid when we are
        // carying out a BLAST where query and subject types are different.
        // Thus we use the blast program here to determine if the type of query != subject.
        $query_multiplier = 1;
        $hit_multiplier = 1;
        // tblastn: query = protein, subject = nucleotide.
        // Thus we need to adjust the hit/subject coordinates.
        if ($blast_program == 'tblastn'){
          $hit_multiplier = 3;
        }
        // blastx: query = nucleotide, subject = protein.
        // Thus we need to adjust the query coordinates.
        elseif ($blast_program == 'blastx'){
          $query_multiplier = 3;
        }

        // Take into account that coordinates can increase or decrease
        // from start to finish of the match (either query and/or subject/hit).
        // By default, we assume everything is increasing then adjust as neccessary.
        $h_from = $hsp['Hsp_hit-from'];
        $h_to = $hsp['Hsp_hit-to'];
        $q_from = $hsp['Hsp_query-from'];
        $q_to = $hsp['Hsp_query-to'];
        if ( $h_from > $h_to){
          $h_to = $hsp['Hsp_hit-from'];
          $h_from = $hsp['Hsp_hit-to'];
        }
        if ( $q_from > $q_to){
          $q_to = $hsp['Hsp_query-from'];
          $q_from = $hsp['Hsp_query-to'];
        }

        // Now foreach chink determined above...
        foreach (array_keys($query) as $k) {

          // Determine the query coordinates.
          $qgap_count = substr_count($query[$k],'-');
          // We also need to take into account the frame when determining the direction
          // of the match. This if the frame is positive then when go from -> to...
          if ($hsp['Hsp_query-frame'] >= 0){
            $coord['qstart'] = ($k == 0) ? $q_from : $coord['qstop'] + 1;
            $coord['qstop'] = $coord['qstart'] + strlen($query[$k]) * $query_multiplier - $qgap_count - 1;
          }
          // whereas, if the frame is negative then we go to -> from.
          else{
            $coord['qstart'] = ($k == 0) ? $q_to : $coord['qstop'] - 1;
            $coord['qstop'] = $coord['qstart'] - strlen($query[$k]) * $query_multiplier - $qgap_count + 1;
          }

          // Determine the subject/hit coordinates.
          $hgap_count = substr_count($hit[$k],'-');
          // We also need to take into account the frame when determining the direction
          // of the match. This if the frame is positive then when go from -> to...
          if ($hsp['Hsp_hit-frame'] >= 0){
            $coord['hstart'] = ($k == 0) ? $h_from : $coord['hstop'] + 1;
            $coord['hstop'] = $coord['hstart'] + strlen($hit[$k]) * $hit_multiplier - $hgap_count - 1;
          }
          // whereas, if the frame is negative then we go to -> from.
          else{
            $coord['hstart'] = ($k == 0) ? $h_to : $coord['hstop'] - 1;
            $coord['hstop'] = $coord['hstart'] - strlen($hit[$k]) * $hit_multiplier - $hgap_count + 1;
          }

          // Pad these coordinates to ensure columned display.
          foreach ($coord as $ck => $val) {
            $pad_type = (preg_match('/start/', $ck)) ? STR_PAD_LEFT : STR_PAD_RIGHT;
            $coord_formatted[$ck] = str_pad($val, $coord_length, '#', $pad_type);
            $coord_formatted[$ck] =  str_replace('#', '&nbsp', $coord_formatted[$ck]);
          }
        ?>
          <div class="alignment-subrow">
            <div class="query">
              <span class="alignment-title">Query:</span>&nbsp;&nbsp;
              <span class="alignment-start-coord"><?php print $coord_formatted['qstart']; ?></span>
              <span class="alignment-residues"><?php print $query[$k]; ?></span>
              <span class="alignment-stop-coord"><?php print $coord_formatted['qstop']; ?></span>
            </div>
            <div class="matches">
              <?php print  str_repeat('&nbsp;', 8); ?>
              <?php print str_repeat('&nbsp;', $coord_length); ?>
              <span class="alignment-residues"><?php print str_replace(' ', '&nbsp', $matches[$k]); ?></span>
            </div>
            <div class="hit">
              <span class="alignment-title">Sbjct:</span>&nbsp;&nbsp;
              <span class="alignment-start-coord"><?php print $coord_formatted['hstart']; ?></span>
              <span class="alignment-residues"><?php print $hit[$k]; ?></span>
              <span class="alignment-stop-coord"><?php print $coord_formatted['hstop']; ?></span>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>

  <?php
    }
  ?>
</div>
