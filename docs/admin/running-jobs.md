# Running jobs automatically

We do not have a tripal daemon like we did with Tripal 3, so as things currently stand,
you need to find a service or method for automatically running users' BLAST jobs.

The simplest option would be to create a line in your system's cron configuration
to run blast jobs using a `drush` command.
However, the finest time resolution for a cron job is one minute, and this
can leave your site users sometimes waiting for more time than is necessary.
We can instead create a systemd service configuration to check for blast jobs more frequently.
Let's say we want to check for blast jobs every 20 seconds.

First set up the service file. Let's call it

```
/etc/systemd/system/tripal_jobs.service
```

Using sudo permissions and your preferred editor, create the file with the following content,
but alter the drush path, username, and directory paths as appropriate for your configuration:

```
[Unit]
Description=Tripal Job Runner
After=network.target

[Service]
Type=oneshot
ExecStart=/usr/local/bin/drush trp-run-jobs --username=drupaladmin --root=/var/www/sitedir
User=www-data
WorkingDirectory=/var/www/sitedir/
StandardOutput=append:/var/log/tripal_jobs.log
StandardError=append:/var/log/tripal_jobs_error.log
```

Next create a systemd timer to run this service every 20 seconds. We will call this file
```
etc/systemd/system/tripal_jobs.timer
```

Create this file with the following content:

```
[Unit]
Description=Runs tripal_jobs.service every 20 seconds

[Timer]
OnBootSec=60s
OnUnitActiveSec=20s
AccuracySec=1s

[Install]
WantedBy=timers.target
```

Now we reload the daemon to register our new service.

```
sudo systemctl daemon-reload
```

Verify that it is running.

```
systemctl status tripal_jobs.timer
```

An example of the output you may see:

```
● tripal_jobs.timer - Runs tripal_jobs.service every 20 seconds
     Loaded: loaded (/etc/systemd/system/tripal_jobs.timer; enabled; preset: enabled)
     Active: active (waiting) since Thu 2026-10-01 01:01:01 CDT; 3 days ago
    Trigger: Mon 2026-10-01 10:10:10 CDT; 15s left
   Triggers: ● tripal_jobs.service

Oct 01 01:01:01 MYSERVER systemd[1]: Started tripal_jobs.timer - Runs tripal_jobs.service every 20 seconds.
```

You will probably want to set up logrotate for the log files.
Because the service is oneshot and run from a timer, a very simple configuration is sufficent.
To rotate log files once a week, create the file

```
/etc/logrotate.d/tripal_jobs
```

with this content:

```
/var/log/tripal_jobs*.log {
    su root root
    weekly
    rotate 7
    compress
    delaycompress
    missingok
    notifempty
}
```

!!! note
This configuration will run any pending tripal job, not just BLAST jobs.
Unless you have a more complex site, this is likely what you will want to do.
