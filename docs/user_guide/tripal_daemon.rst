
Running Jobs Automatically
===========================

BLAST submissions result in the creation of Tripal jobs which then need to run from the command-line. This ensures that long running BLASTs will not cause page time-outs but does add some management overhead and might result in longer waits for users depending on how often you have cron set to run Tripal jobs. You can alternatively use the Tripal Jobs Daemon to automate running of Tripal Jobs reducing user wait time and your own workload.

.. note::

  `Tripal Daemon Documentation <https://tripal.readthedocs.io/en/latest/user_guide/job_management.html>`_

.. warning::

  If you find jobs are not running automatically, you may need to restart the Tripal Daemon. This is also necessary after a server restart. Navigate to your drupal root (e.g. ``/var/www/html``) on the command-line and run:

  .. code:: bash

    drush trpjob-daemon stop
    drush trpjob-daemon start
