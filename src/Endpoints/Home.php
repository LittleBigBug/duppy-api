<?php
namespace Duppy\Endpoints;

use Duppy\Abstracts\AbstractEndpoint;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Home extends AbstractEndpoint {

    /**
     * Set the URI to /
     *
     * @var array
     */
    public static ?array $uri = [ '/' ];

    public static ?array $uriRedirect = [ ["%env:CLIENT_URL", 302] ];

}
