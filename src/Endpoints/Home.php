<?php
namespace Duppy\Endpoints;

use Duppy\Abstracts\AbstractEndpoint;

class Home extends AbstractEndpoint {

    /**
     * Set the URI to /
     *
     * @var ?array
     */
    public static ?array $uri = [ '/' ];

    public static ?array $uriRedirect = [ ["%env:CLIENT_URL", 302] ];

}
