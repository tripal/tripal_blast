
# Adding Tripal BLAST to an existing site via Composer

!!! warning "Under Development"

    This documentation is still being developed as this module is being upgraded.

This module should be download via composer; however, we don't yet have that setup.

In the meantime, you can install this module in an existing Drupal/Tripal site as follows:

1. Download the current module code to the appropriate place in your site. This can be done by replacing DRUPALROOT with the full path to your drupal site. For example, `/var/www/drupal/web`.
```
cd DRUPALROOT/modules/contrib
git clone https://github.com/tripal/tripal_blast
```
2. Enable the module in your site. This can be done on the command line via Drush or through the UI by going to Administration Toolbar > Extend and selecting the checkbox beside Tripal BLAST before clicking "Install".
```
drush en tripal_blast
```

!!! note "Process once Composer is available"

    Once composer install is available, it will be as easy as following the composer instructions on your site to add Tripal BLAST to your composer.json without having composer download it since it is already there.
