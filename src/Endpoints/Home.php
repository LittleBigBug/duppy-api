<?php
namespace Duppy\Endpoints;


use Duppy\Abstracts\AbstractEndpoint;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Home extends AbstractEndpoint
{

    /**
     * Set the URI to / or home
     *
     * @var string
     */
    public static string $uri = "/[home]";

    public function __invoke(Request $request, Response $response, array $args = []): Response
    {
        return $response;
    }

}