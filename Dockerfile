ARG drupalversion='11.x-dev'
ARG phpversion='8.5'
ARG pgsqlversion="18"
FROM tripalproject/tripaldocker:drupal${drupalversion}-php${phpversion}-pgsql${pgsqlversion}

LABEL org.opencontainers.image.source=https://github.com/tripal/tripal_blast
LABEL org.opencontainers.image.description="Provides a demonstration of the Tripal BLAST module installed in the most recent version of Tripal 4"
LABEL org.opencontainers.image.licenses=GPL-3.0-or-later

COPY . /var/www/drupal/web/modules/contrib/tripal_blast

## Set correct config based on versions.
RUN rm -f ./phpunit.xml
RUN bash /var/www/drupal/web/modules/contrib/tripal/set_phpunit_config.sh

## Install latest version of NCBI Blast+.
RUN cd / \
  && version=$(wget -qO- https://ftp.ncbi.nlm.nih.gov/blast/executables/blast+/VERSION) \
  && wget --no-verbose https://ftp.ncbi.nlm.nih.gov/blast/executables/blast+/LATEST/ncbi-blast-${version}+-x64-linux.tar.gz \
  && tar xzf ncbi-blast-${version}+-x64-linux.tar.gz \
  && cp ncbi-blast-${version}+/bin/* /usr/local/bin

## Enable module
WORKDIR /var/www/drupal/web/modules/contrib/tripal_blast
RUN service postgresql restart \
  && drush en tripal_blast --yes

## Set files directory permissions
RUN chown -R www-data:www-data /var/www/drupal \
  && chmod 775 -R /var/www/drupal/web/sites/default/files \
  && usermod -g www-data root
