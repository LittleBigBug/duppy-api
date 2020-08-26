<?php
namespace Duppy\Middleware;

use Duppy\Abstracts\AbstractRouteMiddleware;

class CORSMiddleware extends AbstractRouteMiddleware {

    /**
     * Allow origin from all
     */
    final public function handle() {
        self::$response = self::$response
            ->withHeader("Access-Control-Allow-Origin", "*")
            ->withHeader("Access-Control-Allow-Methods", "GET,HEAD,OPTIONS,POST,PUT")
            ->withHeader("Access-Control-Allow-Headers",
                "Access-Control-Allow-Headers, Origin, Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers");
    }

}
