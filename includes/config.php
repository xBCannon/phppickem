<?php
//modify vars below
// Database
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', 'nflpickem');
define('DB_PASSWORD', '');
define('DB_DATABASE', 'nfl_pickem');
define('DB_PREFIX', 'nflp_');

define('SITE_URL', 'http://yourpickemsite.com/');
define('ALLOW_SIGNUP', true);
define('SHOW_SIGNUP_LINK', true);
define('USER_NAMES_DISPLAY', 3); // 1 = real names, 2 = usernames, 3 = usernames w/ real names on hover
define('COMMENTS_SYSTEM', 'basic'); // basic, disqus, or disabled
define('DISQUS_SHORTNAME', ''); // only needed if using Disqus for comments

define('SITE_NAME', "NFL 2021 Pick 'Em");
define('SEASON_YEAR', '2021');
define('ALWAYS_HIDE_PICKS', false); // Set to true to hide picks until games are locked out
define('SERVER_TIMEZONE', 'America/Phoenix'); // Your SERVER's timezone. NOTE: Game times will always be displayed in Eastern time, as they are on NFL.com. This setting makes sure cutoff times work properly.

// Show donation request in footer
define('ENABLE_DONATE_FOOTER', false);

//define a batch update "key" that a cronjob can pass to update scores automatically
//can be anything you want, as long as it can be sent as a get parameter on the URL
//NOTE: make your life easier and just use alpha-numerics w/out any special chars...
//NOTE: THE PAGE NAME IS CASE SENSEITVE TO BYPASS LOG-IN, IF THE CASE NOT MATCH, IT WILL REDIRECT TO LOGIN W/OUT UPDATING SCORES!
//example:
// curl -O 'http://www.yourdomain.com/getHtmlScores.php?BATCH_SCORE_UPDATE_KEY=yourRandomDefinedValueHere'
// wget 'http://www.yourdomain.com/getHtmlScores.php?BATCH_SCORE_UPDATE_KEY=yourRandomDefinedValueHere'
define('BATCH_SCORE_UPDATE_KEY', 'yourRandomDefinedValueHere');
//enable or disable batch updates here
define('BATCH_SCORE_UPDATE_ENABLED', true);

// ***DO NOT EDIT ANYTHING BELOW THIS LINE***
error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);

//automatically set timezone offset (hours difference between your server's timezone and eastern time)
date_default_timezone_set(SERVER_TIMEZONE);
/*$timeZoneCurrent = @date_default_timezone_get();
if (ini_get('date.timezone')) {
	$timeZoneCurrent = ini_get('date.timezone');
}*/
$dateTimeZoneCurrent = new DateTimeZone(SERVER_TIMEZONE);
$dateTimeZoneEastern = new DateTimeZone("America/New_York");
$dateTimeCurrent = new DateTime("now", $dateTimeZoneCurrent);
$dateTimeEastern = new DateTime("now", $dateTimeZoneEastern);
$offsetCurrent = $dateTimeCurrent->getOffset();
$offsetEastern = $dateTimeEastern->getOffset();
$offsetHours = ($offsetEastern - $offsetCurrent) / 3600;
define('SERVER_TIMEZONE_OFFSET', $offsetHours);
$theTime = time();
$transition = $dateTimeZoneCurrent->getTransitions($theTime,$theTime);
define('SERVER_TIMEZONE_ABBR', $transition[0]['abbr']);
