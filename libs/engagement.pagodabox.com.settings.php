<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

//Database
define('MYSQL_HOST', 'tunnel.pagodabox.com:3306');
define('MYSQL_USER', 'tresa');
define('MYSQL_PASS', '9a8w3hBF');
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

//APIS
define('MAPS_API', 'AIzaSyAcXkDt7ABmkhmdZ_Y_-ECxzycjPIGikiA');
define('FOUR_SQ_API_KEY', 'MSEKRVY1DRH0UHH3UQIERMBTM2BMUCPDFSMGFHYPYJDYUOC0');
define('FOUR_SQ_API_SECRET', 'JSN0Q0P1IFQXSV3K1NAHHFKVJNFV5PAIZAT2UPLO4UCFIMOM');

define('FACEBOOK_API_KEY', '357291564353008');
define('FACEBOOK_API_SECRET', '2590aa5f286391dfb0ef9574531ec430');
define('FACEBOOK_NAMESPACE', 'chrnclapp');
?>
