<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '128M');
//Database
//define('MYSQL_HOST', 'reporting.fn.internal');
define('MYSQL_HOST', ':/Applications/MAMP/tmp/mysql/mysql.sock');
define('MYSQL_USER', 'root');
//define('MYSQL_PASS', 'Zee1suli');
define('MYSQL_PASS', 'root');
define('MYSQL_DB', 'engagement');


//Theme Stuff
define('HOST', 'localhost');
define('SITE_ROOT', 'engagement');
define('PATH_TO_MODULES', 'libs/modules');

//Dev / Live Settings
//any call to elog with a level >= what is defined below will be written to the database
define('DEBUG_LEVEL', 0);
//How often should cron run? (requires the php "server" to be running)
define('CRON_TIME', 60);//seconds

define('FB_KEY', '236032206523485');
define('FB_SECRET', 'c98392cd7bf20deddbb23f01b57ae5ed');
?>
