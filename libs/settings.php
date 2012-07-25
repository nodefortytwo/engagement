<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

//Database
define('MYSQL_HOST', 'reporting.fn.internal');
define('MYSQL_USER', 'root');
define('MYSQL_PASS', 'Zee1suli');
define('MYSQL_DB', '');

//Theme Stuff
define('HOST', 'reporting.vm06.fn.internal');
define('SITE_ROOT', '');
define('PATH_TO_MODULES', 'libs/modules');

//Dev / Live Settings
//any call to elog with a level >= what is defined below will be written to the database
define('DEBUG_LEVEL', 0);
//How often should cron run? (requires the php "server" to be running)
define('CRON_TIME', 60);//seconds
?>
