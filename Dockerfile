FROM tripalproject/tripaldocker:latest

COPY . /var/www/drupal9/web/modules/contrib/tripal_blast

## Install NCBI Blast+.
RUN cd / \
  && wget https://ftp.ncbi.nlm.nih.gov/blast/executables/blast+/2.2.30/ncbi-blast-2.2.30+-x64-linux.tar.gz \
  && tar xzf ncbi-blast-2.2.30+-x64-linux.tar.gz \
  && cp ncbi-blast-2.2.30+/bin/* /usr/local/bin

## Download libraries API dependency.
RUN cd /var/www/drupal9 \
  && composer require 'drupal/libraries:^4.0'

## Enable module
WORKDIR /var/www/drupal9/web/modules/contrib/tripal_blast
RUN service postgresql restart \
  && drush en tripal_blast --yes
