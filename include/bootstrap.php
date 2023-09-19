<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_exception_handler('SunlightPackager\\exception_handler');
set_error_handler('SunlightPackager\\error_handler');
