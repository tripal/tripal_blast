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

## Upgrade to Tripal4 and Drupal10

This module is currently being actively upgraded to Drupal 10. **It should not
yet be installed on a production site and given to users ;-) but we're getting
there! For the current progress, check in the
[upgrade release milestone](https://github.com/tripal/tripal_blast/milestone/1)
to get an idea of the outstanding tasks needed to be fixed before release.

## Automated Testing

This package is dedicated to a high standard of automated testing. We use
PHPUnit for testing and CodeClimate to ensure good test coverage and maintainability.
There are more details on [our CodeClimate project page] describing our specific
maintainability issues and test coverage.

![MaintainabilityBadge]
![TestCoverageBadge]

The following compatibility is proven via automated testing workflows.

![Tripal Version for following tests](https://img.shields.io/badge/Tripal-4.x--dev-green)
[our CodeClimate project page]: https://codeclimate.com/github/tripal/tripal_blast
[MaintainabilityBadge]: https://api.codeclimate.com/v1/badges/5071f91a02a3fcafc275/maintainability
[TestCoverageBadge]: https://api.codeclimate.com/v1/badges/5071f91a02a3fcafc275/test_coverage

| PHP\Drupal | 10.6.x              | 11.3.x              | 11.4.x              |
|------------|---------------------|---------------------|---------------------|
| **PHP8.2** | ![Grid82-106-Badge] |                     |                     |
| **PHP8.3** | ![Grid83-106-Badge] | ![Grid83-113-Badge] | ![Grid83-114-Badge] |
| **PHP8.4** | ![Grid84-106-Badge] | ![Grid84-113-Badge] | ![Grid84-114-Badge] |
| **PHP8.5** |                     | ![Grid85-113-Badge] | ![Grid85-114-Badge] |


[Grid82-106-Badge]: https://github.com/tripal_blast/tripal_blast/actions/workflows/MAIN-phpunit-php8.2_D10_6x.yml/badge.svg
[Grid83-106-Badge]: https://github.com/tripal_blast/tripal_blast/actions/workflows/MAIN-phpunit-php8.3_D10_6x.yml/badge.svg
[Grid83-113-Badge]: https://github.com/tripal_blast/tripal_blast/actions/workflows/MAIN-phpunit-php8.3_D11_3x.yml/badge.svg
[Grid83-114-Badge]: https://github.com/tripal_blast/tripal_blast/actions/workflows/MAIN-phpunit-php8.3_D11_4x.yml/badge.svg
[Grid84-106-Badge]: https://github.com/tripal_blast/tripal_blast/actions/workflows/MAIN-phpunit-php8.4_D10_6x.yml/badge.svg
[Grid84-113-Badge]: https://github.com/tripal_blast/tripal_blast/actions/workflows/MAIN-phpunit-php8.4_D11_3x.yml/badge.svg
[Grid84-114-Badge]: https://github.com/tripal_blast/tripal_blast/actions/workflows/MAIN-phpunit-php8.4_D11_4x.yml/badge.svg
[Grid85-113-Badge]: https://github.com/tripal_blast/tripal_blast/actions/workflows/MAIN-phpunit-php8.5_D11_3x.yml/badge.svg
[Grid85-114-Badge]: https://github.com/tripal_blast/tripal_blast/actions/workflows/MAIN-phpunit-php8.5_D11_4x.yml/badge.svg

## Docker

```
git clone https://github.com/tripal/tripal_blast.git
cd tripal_blast
docker build --tag=tripal/tripal_blast:latest .
docker run --publish=80:80 -tid --volume=`pwd`:/var/www/drupal/web/modules/contrib/tripal_blast tripal/tripal_blast:latest
```
