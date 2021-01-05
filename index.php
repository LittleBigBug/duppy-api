<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 *
 *  For more information, please visit the above link.
 *
 *  This software is private and copyright 2021 (c) LittleBigBug (Ethan Jones)
 *  The state of the software's license may change at any time, and may be sub-licensed out to clients.
 *  For more information on each client's license please visit the above link
 *
 *  'Mods' are extensions of Duppy API created with tools provided by the software.
 *  They are located in the Mods/ directory and each should have author and copyright information
 *  in the info.toml file or the top of the main PHP file
 *  Mods developed for the Duppy API are owned by the owner and are allowed to be sold and licensed separately.
 *  For example, the duppy base API and software can be licensed out but the Mod could be owned or separately licensed.
 *  Client mods will be completely owned solely by clients but the main Duppy software will only be under a limited license.
 */

use Duppy\Bootstrapper\Bootstrapper;

/**
 * Duppy - API for the Dreamin.gg website
 *
 * @package Duppy
 */

$protocol = ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? 'https://' : 'http://');
$prHost = $protocol . $_SERVER["HTTP_HOST"];

$dirName = dirname($_SERVER['SCRIPT_NAME']);

if ($dirName == "/") {
    $dirName = "";
}

define("DUPPY_START", microtime(true));
define("DUPPY_PATH", __DIR__);
define("DUPPY_URI_PATH", $dirName);
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