<?php
use Duppy\Bootstrapper\Bootstrapper;

/**
 * Duppy - API for the Dreamin.gg website
 *
 * @package Duppy
 */

$protocol = ($_SERVER["HTTPS"] == 'on' ? 'https://' : 'http://');

define('DUPPY_START', microtime(true));
define('DUPPY_PATH', __DIR__);
define('DUPPY_URI', $protocol . $_SERVER["HTTP_HOST"] . dirname($_SERVER['SCRIPT_NAME']));

/**
 * Register auto loader.
 */

require DUPPY_PATH . '/vendor/autoload.php';

/**
 * Bootstrap application
 */

Bootstrapper::boot();