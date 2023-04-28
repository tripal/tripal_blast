![Tripal Dependency](https://img.shields.io/badge/Tripal-4.0--alpha1-brightgreen)
![Development Status](https://img.shields.io/badge/Status-Active%20Development-orange)

**Developed by the University of Saskatchewan, Pulse Crop Bioinformatics team.**

## Introduction

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

The BLAST results page is an expandable summary table with each hit being
listed as a row in the table with query/hit/e-value information. The row can
then be expanded to include additional information including the alignment.
Download formats are allow users to download these results in the familiar
tabular, GFF3 or HTML NCBI formats.

## Automated Testing

This package is dedicated to a high standard of automated testing. We use
PHPUnit for testing and CodeClimate to ensure good test coverage and maintainability.
There are more details on [our CodeClimate project page] describing our specific
maintainability issues and test coverage.

![MaintainabilityBadge]
![TestCoverageBadge]

The following compatibility is proven via automated testing workflows.

![Tripal Version for following tests](https://img.shields.io/badge/Tripal-4.x--dev-green)

| Drupal | 9.3.x | 9.4.x | 9.5.x | 10.0.x |
|--------|-------|-------|-------|--------|
| **PHP 8.0** | ![Grid1A-Badge] | ![Grid1B-Badge] | ![Grid1C-Badge] |  |
| **PHP 8.1** | ![Grid2A-Badge] | ![Grid2B-Badge] | ![Grid2C-Badge] |  |

[our CodeClimate project page]: https://codeclimate.com/github/tripal/tripal_blast
[MaintainabilityBadge]: https://api.codeclimate.com/v1/badges/5071f91a02a3fcafc275/maintainability
[TestCoverageBadge]: https://api.codeclimate.com/v1/badges/5071f91a02a3fcafc275/test_coverage

[Grid1A-Badge]: https://github.com/tripal/tripal_blast/actions/workflows/MAIN-phpunit-Grid1A.yml/badge.svg
[Grid1B-Badge]: https://github.com/tripal/tripal_blast/actions/workflows/MAIN-phpunit-Grid1B.yml/badge.svg
[Grid1C-Badge]: https://github.com/tripal/tripal_blast/actions/workflows/MAIN-phpunit-Grid1C.yml/badge.svg

[Grid2A-Badge]: https://github.com/tripal/tripal_blast/actions/workflows/MAIN-phpunit-Grid2A.yml/badge.svg
[Grid2B-Badge]: https://github.com/tripal/tripal_blast/actions/workflows/MAIN-phpunit-Grid2B.yml/badge.svg
[Grid2C-Badge]: https://github.com/tripal/tripal_blast/actions/workflows/MAIN-phpunit-Grid2C.yml/badge.svg
[Grid2D-Badge]: https://github.com/tripal/tripal_blast/actions/workflows/MAIN-phpunit-Grid2D.yml/badge.svg

## Docker

```
git clone https://github.com/tripal/tripal_blast.git
cd tripal_blast
docker build --tag=tripal/tripal_blast:latest .
docker run --publish=80:80 -tid --volume=`pwd`:/var/www/drupal9/web/modules/contrib/tripal_blast tripal/tripal_blast:latest
```
