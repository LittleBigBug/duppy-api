<?php
namespace Duppy\Endpoints;

use Duppy\Abstracts\AbstractEndpoint;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Home extends AbstractEndpoint
{

    /**
     * Set the URI to /
     *
     * @var string
     */
    public static string $uri = "/";

    public function __invoke(Request $request, Response $response, array $args = []): Response
    {
        $client = getenv('CLIENT_URL');

        if ($client == "/") {
            return $response;
        }

        return $response->withAddedHeader('Location', $client);
    }

}