<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 *                             Unit Testing
 */

error_reporting(-1);

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);

date_default_timezone_set("UTC");

$prHost = "http://localhost";

define("DUPPY_START", microtime(true));
define("DUPPY_PATH", dirname(__DIR__));
define("DUPPY_URI_PATH", "");
define("DUPPY_URI", $prHost . DUPPY_URI_PATH);

/**
 * Register auto loader.
 */

require DUPPY_PATH . '/vendor/autoload.php';

/**
 * Bootstrap application
 */

Bootstrapper::boot();