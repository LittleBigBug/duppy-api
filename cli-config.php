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

return ConsoleRunner::createHelperSet(Bootstrapper::getManager());
