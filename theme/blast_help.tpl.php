<?php
/**
 * This template displays the help page for the BLAST UI
 */
?>

<style>
.sub_table {
  border: 0px;
  padding:1px 1px;
  background-color: inherit;
}
</style>

<h3>Tripal BLAST Module Description</h3>
<p>This module provides a basic interface to allow your users to utilize your server's NCBI BLAST+.</p>

<p>
  <a href="#setup">Setup</a> | <a href="#function">Functionality</a>
  | <a href="#protection">Large jobs | <a href="#genomeview">Genome visualization</a>
</p>

<a name="setup"></a>
&mdash;
<h3><b>Setup Instructions</b></h3>
<ol>
  <li>
    Install NCBI BLAST+ on your server (Tested with 2.2.26+). There is a
    <a href="https://launchpad.net/ubuntu/+source/ncbi-blast+">package available
    for Ubuntu</a> to ease installation. Optionally you can set the path to your
    BLAST executable <a href="<?php print url('admin/tripal/extension/tripal_blast/blast_ui');?>">
    in the settings</a>.
  </li>
  <li>
    Optionally, create Tripal External Database References to allow you to link
    the records in your BLAST database to further information. To do this simply
    go to <a href="<?php print url('admin/tripal/chado/tripal_db/add'); ?>" target="_blank">Tripal>
    Chado Modules > Databases > Add DB</a> and make sure to fill in the Database
    prefix which will be concatenated with the record IDs in your BLAST database
    to determine the link-out to additional information. Note that a regular
    expression can be used when creating the BLAST database to indicate what the
    ID is.
  </li>
  <li>
    <a href="<?php print url('node/add/blastdb');?>">Create "BLAST Database"
    nodes</a> for each dataset you want to make available for your users to BLAST
    against. BLAST databases should first be created using the command-line
    <code>makeblastdb</code> program with the <code>-parse_seqids</code> flag.
  </li>
  <li>
    It's recommended that you also install the <a href="http://drupal.org/project/tripal_daemon">Tripal Job Daemon</a>
    to manage BLAST jobs and ensure they are run soon after being submitted by the
    user. Without this additional module, administrators will have to execute the
    tripal jobs either manually or through use of cron jobs.
  </li>
</ol>

<a name="function"></a>
&mdash;
<h3><b>Highlighted Functionality</b></h3>
<ul>
  <li>Supports <a href="<?php print url('blast/nucleotide/nucleotide');?>">blastn</a>,
    <a href="<?php print url('blast/nucleotide/protein');?>">blastx</a>,
    <a href="<?php print url('blast/protein/protein');?>">blastp</a> and
    <a href="<?php print url('blast/protein/nucleotide');?>">tblastx</a> with separate forms depending upon the database/query type.
  </li>
  <li>
    Simple interface allowing users to paste or upload a query sequence and then
    select from available databases. Additionally, a FASTA file can be uploaded
    for use as a database to BLAST against (this functionality can be disabled).
  </li>
  <li>
    Tabular Results listing with alignment information and multiple download
    formats (HTML, TSV, XML) available.
  </li>
  <li>
    Completely integrated with <a href="<?php print url('admin/tripal/tripal_jobs');?>">Tripal Jobs</a>
    providing administrators with a way to track BLAST jobs and ensuring long
    running BLASTs will not cause page time-outs
  </li>
  <li>
    BLAST databases are made available to the module by
    <a href="<?php print url('node/add/blastdb');?>">creating Drupal Pages</a>
    describing them. This allows administrators to
    <a href="<?php print url('admin/structure/types/manage/blastdb/fields');?>">use the Drupal Field API to add any information they want to these pages</a>.
  </li>
  <li>
    BLAST database records can be linked to an external source with more
    information (ie: NCBI) per BLAST database.
  </li>
</ul>

<a name="protection"</a></a>
&mdash;
<h3><b>Protection Against Large Jobs</b></h3>
Depending on the size and nature of your target databases, you may wish to constrain use
of this module.
<ol>
  <li>Limit the number of results displayed via admin page. The recommended number is 500.</li>
  <li>
    Limit the maximum upload file size in php settings. This is less useful because some
    very large queries may be manageable, and others not.
  </li>
  <li>
    Repeat-mask your targets, or provide repeat-masked versions. Note that some
    researchers may be looking for repeats, so this may limit the usefulness of the BLAST
    service.
  </li>
</ol>

<a name="genomeview"></a>
&mdash;
<h3><b>Whole Genome Visualization</b></h3>
This module can be configured to use
<a href="https://github.com/LegumeFederation/cvitjs">CViTjs</a> to display BLAST hits on
a genome image.

<h4>CViTjs Setup</h4>
<ol>
  <li>
    <a href="https://github.com/LegumeFederation/cvitjs">Download CViTjs</a> and copy
    the code to your webserver. It needs to be placed in <code>[your drupal root]/sites/all/libraries</code>. To download, execute
    the git command inside the <code>libraries/</code> directory:<br>
    <code>git clone https://github.com/LegumeFederation/cvitjs.git</code>
  </li>
  <li>
    CViTjs will have a config file in its root directory named cvit.conf. This file
    provides information for whole genome visualization for each genome BLAST target.
    <b>Make sure the config file can be edited by your web server.</b>
  </li>
  <li>
    Enable CViTjs from the BLAST module administration page.
  </li>
  <li>
    Edit the configuration file to define each genome target. These will look like:
    <pre>
[data.Cajanus cajan - genome]
conf = data/cajca/cajca.conf
defaultData = data/cajca/cajca.gff</pre>
    Where:<br>
    <ul>
      <li>the section name, "data.Cajanus cajan - genome", consists of "data." followed
          by the name of the BLAST target node,</li>
      <li>the file "cajca.conf" is a cvit configuration file which describes how to draw the
          chromosomes and BLAST hits on the <i>Cajanus cajan</i> genome,</li>
      <li>and the file "cajca.gff" is a GFF3 file that describes the <i>Cajanus cajan</i>
          chromosomes.</li>
    </ul>
    At the top of the configuration file there must be a [general] section that defines
    the default data set. For example:
    <pre>
[general]
data_default = data.Cajanus cajan - genome</pre>
  </li>
  <li>
    Edit the nodes for each genome target (nodes of type "BLAST Database") and enable whole
    genome visualization. Remember that the names listed in the CViTjs config file must
    match the BLAST node name. In the example above, the BLAST database node for the
    <i>Cajanus cajan</i> genome assembly is named "Cajanus cajan - genome"
  </li>
</ol>

<h4>Notes</h4>
<ul>
<li>The .conf file for each genome can be modified to suit your needs and tastes. See the
  sample configuration file, <code>data/test1/test1.conf</code>, and the CViTjs
  <a href="https://github.com/LegumeFederation/cvitjs#using-cvitjs">documentation</a>.</li>
<li>Each blast target CViTjs configuration file must define how to visualize blast hits or you will not see them.
  <pre>[blast]
feature = BLASTRESULT:match_part
glyph   = position
shape = rect
color   = #FF00FF
width = 5</pre></li>
<li>You will have to put the target-specific conf and gff files (e.g. cajca.conf and
  cjca.gff) on your web server, in the directory, <code>sites/all/libraries/cvitjs/data</code>. You may
  choose to group files for each genome into subdirectories, for example,
  <code>sites/all/libraries/cvitjs/data/cajca</code>.</li>
<li>It is important to make sure that cvit.conf points to the correct data directory and the
  correct .gff and .conf files for the genome in question. For more information about how to
  create the .gff file, see the
  <a href="https://github.com/LegumeFederation/cvitjs#how-to">documentation</a>.</li>
</ul>
