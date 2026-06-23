ARG drupalversion='11.x-dev'
ARG phpversion='8.5'
ARG pgsqlversion="18"
FROM tripalproject/tripaldocker:drupal${drupalversion}-php${phpversion}-pgsql${pgsqlversion}

LABEL org.opencontainers.image.source=https://github.com/tripal/tripal_blast
LABEL org.opencontainers.image.description="Provides a demonstration of the Tripal BLAST module installed in the most recent version of Tripal 4"
LABEL org.opencontainers.image.licenses=GPL-3.0-or-later

COPY . /var/www/drupal/web/modules/contrib/tripal_blast

## Install NCBI Blast+.
RUN cd / \
  && wget https://ftp.ncbi.nlm.nih.gov/blast/executables/blast+/2.2.30/ncbi-blast-2.2.30+-x64-linux.tar.gz \
  && tar xzf ncbi-blast-2.2.30+-x64-linux.tar.gz \
  && cp ncbi-blast-2.2.30+/bin/* /usr/local/bin

## Enable module
WORKDIR /var/www/drupal/web/modules/contrib/tripal_blast
RUN service postgresql restart \
  && drush en tripal_blast --yes

## Set files directory permissions
RUN chown -R www-data:www-data /var/www/drupal \
  && chmod 775 -R /var/www/drupal/web/sites/default/files \
  && usermod -g www-data root
