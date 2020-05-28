<?php
namespace Duppy\Bootstrapper;

use Duppy\Util;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class Router
{

    /**
     * Map of routes
     *
     * @var array
     */
    public array $routes = [];

    /**
     * Path of endpoint sources
     *
     * @var string
     */
    public string $endpointsSrc;

    /**
     * Prefix to put before all URIs in this Router
     *
     * @var string
     */
    public string $uriPrefix = '';

    /**
     * Router constructor.
     *
     * @param string $uriPrefix
     */
    public function __construct(string $uriPrefix = '')
    {
        $this->endpointsSrc = 'Endpoints';
        $this->uriPrefix = $uriPrefix;
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

            $route = Bootstrapper::getApp()->$type($endpoint['uri'], $class);

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
        $searchPath = Util::combinePaths(array(DUPPY_PATH, "src", $this->endpointsSrc));
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($searchPath));

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
            $path = substr(Util::toProjectPath($path), strlen('src/'));
            $class = 'Duppy\\' . $path;

            $uri = $class::getUri() ?: $this->resolveUri($path);

            if ($uri !== $class::getUri()) {
                $class::$uri = $uri;
            }

            $fn([
                'class' => $class,
                'uri' => $uri,
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
        $path = substr($path, strlen($this->endpointsSrc));
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