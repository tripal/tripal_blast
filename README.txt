

################################################################################
INTRODUCTION
------------
This module provides a basic interface to allow your users to utilize your 
server's NCBI BLAST+.

Specifically it provides two forms, one for nucleotide queries and another for 
protein queries. Currently only blastn and blastp are supported but in the future 
you will be able to select either a nucleotide or a protein database to BLAST 
against regardless of the type of query and this module will decide which BLAST 
program to use based on the combination of query/database type (ie: if you 
selected a protein database on the nucleotide BLAST form then blastx would 
be used).

BLAST submissions result in the creation of Tripal jobs which then need to run 
from the command-line. This ensures that long running BLASTS will not cause 
page time-outs but does add some management overhead and might result in longer 
waits for users depending on how often you have cron set to run Tripal jobs. 
A Tripal Jobs Daemon is under development to allow these jobs to be run almost 
as soon as they are submitted.

The BLAST results page is an expandable summary table with each hit being 
listed as a row in the table with query/hit/e-value information. The row can 
then be expanded to include additional information including the alignment. 
Download formats are under development to allow users to download these 
results in the familiar tabular or HTML NCBI formats.

Highlighted Functionality
-------------------------
 - Supports blastn and blastp with separate forms depending upon the query 
   type.
 - Simple interface allowing users to paste or upload a query sequence and 
   then select from available databases. Additionally, a FASTA file can be 
   uploaded for use as a database to BLAST against.
 - Tabular Results listing with alignment information available.
 - Completely integrated with Tripal Jobs providing administrators with a 
   way to track BLAST jobs and ensuring long running BLASTs will not cause 
   page time-outs
 - BLAST databases are made available to the module by creating Drupal Pages 
   describing them. This allows administrators to use the Drupal Field API to 
   add any information they want to these pages.

Installation
------------
1. Install NCBI BLAST+ on your server (Tested with 2.2.26+). There is a 
   package available for Ubuntu to ease installation.
2. Install this module as you would any Drupal module (ie: download, unpack 
   in sites/all/modules and enable through http://[your site]/admin/modules)
3. Create "Blast Database" nodes for each dataset you want to make available 
   for your users to BLAST against. BLAST databases should first be created 
   using the command-line makeblastdb program with the -parse_seqids flag.

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
