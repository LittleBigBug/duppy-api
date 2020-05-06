<?php
namespace Duppy\Endpoints;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Middleware\TestMiddleware;
use Duppy\Entities\WebUser;

class ExampleEndpoint extends AbstractEndpoint
{
    /**
     * Type of request
     *
     * @var string
     */
    public static string $type = 'get';

    /**
     * Route middleware
     *
     * @var array
     */
    public static array $middleware = [
        TestMiddleware::class,
    ];

    /**
     * Handles the response
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    final public function __invoke(Request $request, Response $response, array $args = []): Response
    {
        // TODO: can probably clean this up if I extend Slim app
        $database = self::getContainer()->get('database');

        $user = new WebUser;
        $user->setSteamid64('76561198316387873');
        $user->setUsername('havasu');
        $user->setBio('Hello world');
        $user->setEmail('havasuited@gmail.com');

        $database->persist($user);
        $database->flush();

        $response->getBody()->write('Test');
        return $response;
    }
}