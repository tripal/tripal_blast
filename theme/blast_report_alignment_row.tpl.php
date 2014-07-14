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

<div class="title">Alignment</div>

<?php
  foreach($HSPs as $hsp) {
?>

  <div class="hsp-title">HSP <?php print $hsp['Hsp_num']?></div>
  <div class="alignment-metrics">
    <span class="identity">
      Identity=&nbsp;
      <?php print $hsp['Hsp_identity']; ?>/<?php print $hsp['Hsp_align-len']; ?> (<?php print $hsp['Hsp_identity']/$hsp['Hsp_align-len']*100;?>%)
    </span>,&nbsp;
    <span class="positive">
      Positive=&nbsp;
      <?php print $hsp['Hsp_positive']; ?>/<?php print $hsp['Hsp_align-len']; ?> (<?php print $hsp['Hsp_positive']/$hsp['Hsp_align-len']*100;?>%)
    </span>
  </div>
  <div class="alignment">
    <div class="alignment-row">
      <div class="query">
        <span class="alignment-title">Query:</span>&nbsp;&nbsp;&nbsp;
        <span class="alignment-residues"><?php print $hsp['Hsp_qseq']; ?></span>
      </div>
      <div class="matches">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <span class="alignment-residues"><?php print $hsp['Hsp_midline']; ?></span>
      </div>
      <div class="subject">
        <span class="alignment-title">Subject:</span>&nbsp;
        <span class="alignment-residues"><?php print $hsp['Hsp_hseq']; ?></span>
      </div>
    </div>
  </div>

<?php
  }
?>
