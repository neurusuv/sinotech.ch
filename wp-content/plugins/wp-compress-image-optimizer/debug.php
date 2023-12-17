<?php

define('WPS_IC_DEBUG_LOG', false);

if (get_option('wps_ic_debug') == 'true') {
	ini_set('display_errors', 1);
  error_reporting(E_ALL);
}
else {
	ini_set('display_errors', 0);
	error_reporting(0);
	define('WPS_IC_DEBUG', 'false');
}

if (get_option('wps_ic_debug') == 'log') {
	define('WPS_IC_DEBUG', 'true');
}

//if (!empty($_GET['tmpDbg'])) {
//  ini_set('display_errors', 1);
//  error_reporting(E_ALL);
//}