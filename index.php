<?php

/*

  Duppy

*/

use Slim\Factory\AppFactory;

require("vendor/autoload.php");

$AppStart = time();

// Slim Framework Application
$app = AppFactory::create();

$group->get("", function($req, $resp, $args) {
  die("");
});

$app->run();

?>
