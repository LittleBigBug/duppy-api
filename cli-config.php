<?php
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Duppy\Bootstrapper\Bootstrapper;

/**
 * Register auto loader.
 */

require __DIR__ . '/vendor/autoload.php';

/**
 * Configure doctrine CLI
 */

Bootstrapper::configureDatabase();
return ConsoleRunner::createHelperSet(Bootstrapper::getManager());
