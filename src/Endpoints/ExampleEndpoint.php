<?php
namespace Duppy\Endpoints;

use DI\DependencyException;
use DI\NotFoundException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Middleware\TestMiddleware;
use Duppy\Entities\Category;
use Duppy\Entities\WebUser;
use Duppy\Entities\Thread;
use Duppy\Entities\Post;

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
     * @throws DependencyException
     * @throws NotFoundException
     */
    final public function __invoke(Request $request, Response $response, array $args = []): Response
    {
        $database = self::getContainer()->get('database');

        $user = new WebUser([
            'steamid64' => (string) random_int(0, 999),
            'username' => 'havasu',
            'bio' => 'Hello',
            'email' => 'havasuited@gmail.com'
        ]);

        $post = new Post([
            'content' => 'Hello',
        ]);

        $category = new Category([
            'title' => 'Hello',
            'slug' => 'hello',
            'order_num' => 1
        ]);

        $thread = new Thread([
            'title' => 'Hello',
        ]);

        $database->persist($user);
        $database->persist($post);
        $database->persist($category);
        $database->persist($thread);
        $database->flush();

        $response->getBody()->write('Test');
        return $response;
    }
}