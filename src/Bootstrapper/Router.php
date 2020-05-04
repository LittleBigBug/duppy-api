<?php
namespace Duppy\Bootstrapper;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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
    }

    /**
     * Build slim routes
     */
    public function build(): void
    {
        $this->loop(function (array $endpoint) {
            $class = $endpoint['class'];
            $type = $class::getType();
            $middleware = $class::getMiddleware();

            $route = $this->app->$type($endpoint['uri'], $class);

            foreach ($middleware as $ware) {
                $route->add(new $ware);
            }
        });
    }

    /**
     * Resolves all endpoints
     *
     * @param callable $fn
     */
    private function loop(callable $fn): void
    {
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
        // TODO: clean up this function, it's a mess lol.
        $path = substr($path, 9);
        $uri = str_replace('\\', '/', $path);

        // Add dashes between words
        $uri = preg_replace('/(?<![_])\B([A-Z])/', '-$1', $uri);

        return strtolower($this->parseVariables($uri));
    }

    /**
     * Parses route variables in the URI
     *
     * @param string $uri
     * @return string
     */
    private function parseVariables(string $uri): string
    {
        $exploded = explode('/', $uri);
        $imploded = [];

        foreach ($exploded as $string) {
            if (strpos($string, '_') === 0) {
                // Remove underscore
                $sub = substr($string, 1);
                $imploded[] = '{'. $sub .'}';
                continue;
            }
            // If no variables return default
            $imploded[] = $string;
        }

        return implode('/', $imploded);
    }
}