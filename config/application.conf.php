<?php


$initConfigs = parse_ini_file(__DIR__ . "/application.ini");
foreach ($initConfigs as $c => $cfg) {
    if (!defined($c)) {
        define($c, $cfg);
    }
}

if(defined('APP_TIMEZONE')) {
    date_default_timezone_set(APP_TIMEZONE);
}

