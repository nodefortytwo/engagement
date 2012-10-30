<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

//Database
define('MYSQL_HOST', 'tunnel.pagodabox.com:3306');
define('MYSQL_USER', 'carie');
define('MYSQL_PASS', 'uavdqCwp');
define('MYSQL_DB', 'engagement');

//Theme Stuff
define('HOST', 'engagement.pagodabox.com');
define('SITE_ROOT', '');
define('PATH_TO_MODULES', 'libs/modules');

define('UPLOAD_PATH', 'uploads');

//Dev / Live Settings
//any call to elog with a level >= what is defined below will be written to the database
define('DEBUG_LEVEL', 0);
//How often should cron run? (requires the php "server" to be running)
define('CRON_TIME', 60);//seconds
define('TRACE', false);

define('FB_KEY', '236032206523485');
define('FB_SECRET', 'c98392cd7bf20deddbb23f01b57ae5ed');
?>
