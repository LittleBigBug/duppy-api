<?php
namespace Duppy\Bootstrapper;

use Duppy\Endpoints\ExampleEndpoint;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Slim\App;

final class Router
{
    /**
     * Slim instance
     *
     * @var App|null
     */
    public App $app;

    /**
     * Map of routes
     *
     * @var array
     */
    public array $routes = [];

    /**
     * Router constructor.
     */
    public function __construct()
    {
        // Get slim instance
        $this->app = Bootstrapper::getApp();

        $this->app->get('/hello/{name}', function (Request $request, Response $response, $args) {
            $name = $args['name'];
            $response->getBody()->write("Hello, $name");
            return $response;
        });
    }

    /**
     * Builds all the routes for Slim
     */
    public function build(): void
    {
        $this->loop(function (array $endpoint) {
            $class = $endpoint['class'];
            $type = $class::getType();
            echo $endpoint['uri'];
            echo '<br>';
            $this->app->$type($endpoint['uri'], $class);
        });
    }

    /**
     * Resolves all endpoints
     *
     * @param callable $fn
     */
    private function loop(callable $fn): void
    {
        // TODO: Implement logic for arguments in uri

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../Endpoints'));

        foreach ($iterator as $file) {
            // Check if file is a valid file
            if (!$file->isFile()) {
                continue;
            }

            // Get pathname
            $path = $file->getRealPath() ?: $file->getPathname();

            // Check file extension
            if ('php' !== pathinfo($path, PATHINFO_EXTENSION)) {
                continue;
            }

            // Resolve class
            $path = str_replace('.php', '', $path);
            $path = explode('src' . DIRECTORY_SEPARATOR, $path);
            $class = 'Duppy\\' . $path[1];

            $fn([
                'class' => $class,
                'uri' => $this->resolveUri($path[1]),
            ]);
        }
    }

    /**
     * Resolves the endpoint URI from the class path
     *
     * @param string $path
     * @return string
     */
    private function resolveUri(string $path): string
    {
        // TODO: try and do this is one regex

        $path = substr($path, 9);
        $uri = str_replace('\\', '/', $path);

        // Add dashes between words
        $uri = preg_replace('/(?<![_])\B([A-Z])/', '-$1', $uri);

        return strtolower($uri);
    }
}