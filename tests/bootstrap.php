<?php

error_reporting(-1);

if (false) {
    define('TEST_DSN', '');
}

require_once __DIR__ . '/../vendor/autoload.php';


if (!function_exists('posix_geteuid')) {
    function posix_geteuid()
    {
        return 999;
    }
}

if (!function_exists('posix_getpwuid')) {
    function posix_getpwuid($uid)
    {
        return ['name' => $uid === 999 ? 'dummyuser' : 'unknownuser'];
    }
}
