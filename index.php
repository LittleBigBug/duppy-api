<?php
use Duppy\Bootstrapper\Bootstrapper;

/**
 * Duppy - API for the Dreamin.gg website
 *
 * @package Duppy
 */

$protocol = ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? 'https://' : 'http://');
$prHost = $protocol . $_SERVER["HTTP_HOST"];

define("DUPPY_START", microtime(true));
define("DUPPY_PATH", __DIR__);
define("DUPPY_URI_PATH", dirname($_SERVER['SCRIPT_NAME']));
define("DUPPY_URI", $prHost . DUPPY_URI_PATH);
define("DUPPY_FULL_URL", $prHost . $_SERVER["REQUEST_URI"]);

/**
 * Register auto loader.
 */

require DUPPY_PATH . '/vendor/autoload.php';

/**
 * Bootstrap application
 */

Bootstrapper::boot();