
INTRODUCTION
------------
This module provides a basic interface to allow your users to utilize your
server's NCBI BLAST+.

Specifically it provides blast program-specific forms (blastn, blastp, tblastn, 
blastx are supported). In the future, there will be a single form where you 
will be able to select either a nucleotide or a protein database to BLAST
against regardless of the type of query and it will decide which BLAST
program to use based on the combination of query/database type (ie: if you
selected a protein database on the nucleotide BLAST form then blastx would
be used).

BLAST submissions result in the creation of Tripal jobs which then need to run
from the command-line. This ensures that long running BLASTs will not cause
page time-outs but does add some management overhead and might result in longer
waits for users depending on how often you have cron set to run Tripal jobs.
You can alternatively use the [Tripal Jobs Daemon](https://github.com/tripal/tripal/tree/7.x-3.x/tripal_daemon) 
to automate running of Tripal Jobs reducing user wait time and your own workload.

The BLAST results page is an expandable summary table with each hit being
listed as a row in the table with query/hit/e-value information. The row can
then be expanded to include additional information including the alignment.
Download formats are allow users to download these results in the familiar 
tabular, GFF3 or HTML NCBI formats.

Highlighted Functionality
-------------------------
 - Supports blastn, blastp, tblastn, and blastx with separate forms depending 
   upon the query type.
 - Simple interface allowing users to paste or upload a query sequence and
   then select from available databases. Additionally, a FASTA file can be
   uploaded for use as a database to BLAST against.
 - Tabular Results listing with alignment information available.
 - Completely integrated with Tripal Jobs providing administrators with a
   way to track BLAST jobs and ensuring long running BLASTs will not cause
   page time-outs
 - BLAST databases are made available to the module by creating Drupal Pages
   describing them. This allows administrators to use the Drupal Field API to
   add any information they want to these pages and to control which databases
   are available to a given user based on native Drupal permissions.
 - Per Query result diagrams visualizing the HSPs to help users better 
   evaluate hit quality.
 - Optional Whole Genome diagrams visualizing the distribution of hits which
   are configurable per Blast Database.

Installation
------------
1. Install NCBI BLAST+ on your server (Tested with 2.2.26+). There is a
   package available for Ubuntu to ease installation.
2. Install this module as you would any Drupal module (ie: download, unpack
   in sites/all/modules and enable through http://[your site]/admin/modules)
3. Create "Blast Database" nodes for each dataset you want to make available
   for your users to BLAST against. BLAST databases should first be created
   using the command-line makeblastdb program with the -parse_seqids flag.
   
Set-up of Whole Genome Diagrams with CViTjs
--------------------------------------------
1. Download [CViTjs](https://github.com/LegumeFederation/cvitjs) and extract
   it at sites/all/libraries
2. Configure it via the Tripal Blast administration settings at 
   http://[your site]admin/tripal/extension/tripal_blast/settings
3. Enable it for specific Blast Databases by editing the corresponding
   Tripal "Blast Database" Drupal node.
   
For more in depth help, reference the Help tab at 
http://[your site]/admin/tripal/extension/tripal_blast/help

Customization
-------------
The BLAST module forms can be styled using CSS stylesheets in your own theme.
By default it will use the default form themeing provided by your particular
Drupal site allowing it to feel consistent with the look-and-feel of your
Tripal site without customization being needed.

Additionally, the results page, waiting pages and the alignment section of
the results page have their own template files (blast_report.tpl.php,
blast_report_pending.tpl.php, and blast_report_alignment_row.tpl.php,
respectively) which can easily be overridden in your own theme providing
complete control over the look of the BLAST results.
