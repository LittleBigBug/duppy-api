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
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    final public function __invoke(Request $request, Response $response, array $args = []): Response
    {
        // TODO: can probably clean this up if I extend Slim app
        $database = self::getContainer()->get('database');

        $user = new Webuser([
            'steamid64' => random_int(0, 999),
            'username' => 'havasu',
            'bio' => 'Hello world',
            'email' => 'havasuited@gmail.com'
        ]);

        $database->persist($user);
        $database->flush();

        $response->getBody()->write('Test');
        return $response;
    }
}