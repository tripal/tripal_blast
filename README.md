![Tripal Dependency](https://img.shields.io/badge/tripal-%3E=3.0-brightgreen)
[![Tripal Rating Gold Status](https://tripal.readthedocs.io/en/7.x-3.x/_images/Tripal-Gold.png)](https://tripal.readthedocs.io/en/7.x-3.x/extensions/module_rating.html#Gold)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/tripal/tripal_blast)

[![Build Status](https://travis-ci.org/tripal/tripal_blast.svg?branch=7.x-1.x)](https://travis-ci.org/tripal/tripal_blast)
[![Maintainability](https://api.codeclimate.com/v1/badges/5071f91a02a3fcafc275/maintainability)](https://codeclimate.com/github/tripal/tripal_blast/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/5071f91a02a3fcafc275/test_coverage)](https://codeclimate.com/github/tripal/tripal_blast/test_coverage)

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

Installation
------------
1. Install NCBI BLAST+ on your server (Tested with 2.2.26+). Please use the
   [official NCBI installation documentation](https://www.ncbi.nlm.nih.gov/books/NBK52640/)
   for your server.
2. Install this module as you would any Drupal module (ie: download, unpack
   in sites/all/modules and enable through http://[your site]/admin/modules)
3. Create "Blast Database" nodes for each dataset you want to make available
   for your users to BLAST against. BLAST databases should first be created
   using the command-line makeblastdb program with the -parse_seqids flag.

 It's recommended that you also install the [Tripal Job Daemon](https://github.com/tripal/tripal/tree/7.x-3.x/tripal_daemon)
 to manage BLAST jobs and ensure they are run soon after being submitted
 by the user. Without this additional module, administrators will have to
 execute the Tripal jobs either manually or through use of cron jobs.

Documentation   [![Documentation Status](https://readthedocs.org/projects/tripal-blast-ui/badge/?version=latest)](https://tripal-blast-ui.readthedocs.io/en/latest/?badge=latest)
--------------

We have an extensive [user guide](https://tripal-blast-ui.readthedocs.io/en/latest/user_guide.html) and a [developer guide](https://tripal-blast-ui.readthedocs.io/en/latest/dev_guide.html) available via [readthedocs](https://tripal-blast-ui.readthedocs.io/en/latest/index.html).

Comparison with other Modules
------------------------------
<table>
<tr><th></th><th><a href="project/tripal_blast">Tripal BLAST UI</a></th><th><a href="project/tripal_blast_analysis">Tripal BLAST Analysis</a></th></tr>
<tr><th>Provides an interface to execute BLASTs</th><td>Yes</td><td>No</td></tr>
<tr><th>Display BLAST Results to users</th><td>After BLAST submission</td><td>On associated feature pages</td></tr>
<tr><th>Load BLAST Results into Chado</th><td>No</td><td>Yes</td></tr>
</table>

**NOTE: These modules will be combined into a single download available
here in Tripal 4.**

Future Development
-------------------
 - Improvements to XML reader for support of larger result sets
 - The ability to blast against 2+ datasets at the same time
 - Ability to Email user when BLAST is done
 - Automatic cleaning up of BLAST job files after 1 week (make time frame configurable)

 Testing or Development via Docker
 -----------------------------------

 Testing or development of this module can be done through laceysanderson's Drupal 7, Tripal 3 docker image using the following instructions.

 1) Pull the most recent image from the Github Package Repository.

 ```
 docker pull laceysanderson/drupal7dev
 ```

 2) Pull the Tripal BLAST module. I suggest creating a dockers directory to ensure you can find the directory mapped to your container ;-p

 ```
 cd ~/Dockers
 git clone https://github.com/tripal/tripal_blast
 cd tripal_blast
 ```

 3) Create the needed .env file. This will not be committed to the repository since it's included in the .gitignore file and could provide security issues.

 ```
 touch .env
 ```

 Now edit this file with your favourite editor to include the following variables. Make sure to change the values for security reasons!

 ```
 ##
 ## DO NOT REMOVE ANY VARIABLES.
 ##
 DBADMIN=tripaladmin
 DBNAME=tripaldb
 DRUPALADMIN=tripaladmin
 DRUPALEMAIL=tripaladmin@yourserver.com
 SITENAME="Tripal BLAST Docker"
 ```

 4) Create a running container exposing the website at localhost:8888 and mounting your current directory inside the container.

  - **Make sure to change `DBPASS` and `ADMINPASS` for security reasons.**
  - Your website admin is the value of `DRUPALADMIN` in the .env file with the password set in the run command below.

 ```
 docker run --publish=8888:80 --name=tblast -tid \
   -e DBPASS='somesecurepassword' \
   -e ADMINPASS='anothersecurepassword' \
   --env-file=.env \
   --volume=`pwd`:/var/www/html/sites/all/modules/tripal_blast \
   laceysanderson/drupal7dev:latest
 ```

 5) Provision the container including installation of the software stack including default configuration. This step will take a few minutes.

 ```
 docker exec -it tblast /app/init_scripts/startup_container.sh
 ```

**NOTE: You will need to install NCBI Blast+ and this module on the docker container as we have not yet automated this process.**

Now any changes you make in your current directory will be mirrored within the `tblast` docker container. You can interact with the Tripal site at localhost:8888 in your browser.
