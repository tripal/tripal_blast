# Overview

This module provides a basic interface to allow your users to utilize your server's NCBI BLAST+.

Specifically it provides blast program-specific forms (blastn, blastp, tblastn, blastx are supported) where users can input their query sequence and choose from an admin configured list of available target databases to align their query sequence against.

BLAST submissions result in the creation of Tripal jobs which then need to run from the command-line. This ensures that long running BLASTs will not cause page time-outs but does add some management overhead and might result in longer waits for users depending on how often you have cron set to run Tripal jobs.

The BLAST results page is an expandable summary table with each hit being listed as a row in the table with query/hit/e-value information. The row can then be expanded to include additional information including the alignment. Download formats are allow users to download these results in the familiar tabular, GFF3 or HTML NCBI formats.
