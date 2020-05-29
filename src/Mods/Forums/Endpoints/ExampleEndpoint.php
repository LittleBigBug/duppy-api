<?php
namespace Duppy\Mods\Forums\Endpoints;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Mods\Forums\Entities\Category;
use Duppy\Mods\Forums\Entities\Post;
use Duppy\Mods\Forums\Entities\Thread;
use Duppy\Mods\Forums\Entities\WebUser;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Duppy\Abstracts\AbstractEndpoint;

class ExampleEndpoint extends AbstractEndpoint
{
    /**
     * Types of requests accepted
     *
     * @var array
     */
    public static array $types = [ 'get' ];

    /**
     * Route middleware
     *
     * @var array
     */
    public static array $middleware = [];

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