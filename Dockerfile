FROM tripalproject/tripaldocker:latest

COPY . /var/www/drupal9/web/modules/contrib/tripal_blast

RUN cd /var/www/drupal9 \
  && composer require 'drupal/libraries:^4.0'

WORKDIR /var/www/drupal9/web/modules/contrib/tripal_blast

RUN service postgresql restart \
  && drush en tripal_blast --yes
