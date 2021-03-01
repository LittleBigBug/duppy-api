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

use Duppy\DuppyServices\Env;
use Workerman\Connection\ConnectionInterface;
use Workerman\Protocols\Http;
use Workerman\Psr7\Response;
use Workerman\Psr7\ServerRequest;
use Workerman\Worker;
use Duppy\Bootstrapper\Bootstrapper;

/**
 * Duppy - API for the Dreamin.gg website
 * Server based on Workerman
 *
 * @package Duppy
 */


define("DUPPY_APP_START", microtime(true));
define("DUPPY_PATH", __DIR__);
define("DUPPY_URI_PATH", "");

/**
 * Register auto loader.
 */

require DUPPY_PATH . '/vendor/autoload.php';

/**
 * Bootstrap application
 */

Bootstrapper::boot();

/**
 * Workerman server
 */

$workers = Env::G("WORKERMAN_WORKERS") ?? 4;
$port = Env::G("WORKERMAN_PORT") ?? 8900;

$worker = new Worker("http://0.0.0.0:$port");
$worker->count = $workers;

// PSR-7 Support
Http::requestClass(ServerRequest::class);

$worker->onMessage = function(ConnectionInterface $connection, ServerRequest $request) {
    Bootstrapper::$duppy_req_start = microtime(true);

    // Handle slim application
    $app = Bootstrapper::getApp();
    $response = $app->handle($request);

    // Convert up to workerman response
    $wmResponse = new Response($response->getStatusCode(), $response->getHeaders(), $response->getBody(), $response->getProtocolVersion(), $response->getReasonPhrase());
    $connection->send($wmResponse);
};

Worker::runAll();