<?php
use Duppy\Bootstrapper\Bootstrapper;

/**
 * Duppy - API for the Dreamin.gg website
 *
 * @package Duppy
 */

define('DUPPY_START', microtime(true));

/**
 * Register auto loader.
 */

require __DIR__ . '/vendor/autoload.php';

/**
 * Bootstrap application
 */

(new Bootstrapper)->boot();