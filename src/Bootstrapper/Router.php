<?php
namespace Duppy\Bootstrapper;

use Duppy\Util;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

final class Router {

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
    public function __construct(string $uriPrefix = '') {
        $this->endpointsSrc = 'Endpoints';
        $this->uriPrefix = $uriPrefix;
    }

    /**
     * Build slim routes
     */
    public function build(): void {
        $endPoints = [];
        $endPointGroups = [];

        // Get all the endpoints & groups from filesystem
        $this->loop(function (array $endpoint) use(&$endPoints, &$endPointGroups) {
            $class = $endpoint['class'];
            $parent = $class::getParentGroupEndpoint();

            $endpoint['parent'] = $parent;

            switch ($endpoint['type']) {
                case 'endpoint':
                    $endPoints[] = $endpoint;
                    break;
                case 'group':
                    $endPointGroups[] = $endpoint;
                    break;
            }
        });

        // Sort the endpoint groups to properly create parent hierarchy
        $endPointGroups = self::sortByParentLoadOrder($endPointGroups);

        $finalEndpoints = [];
        $loneEndpoints = [];
        $endPointGroupMap = [];

        // Create a map to easily get groupmap from classname. This also is more efficient to index
        foreach ($endPointGroups as $key => $group) {
            $class = $group['class'];
            $endPointGroupMap[$class] = $key;
        }

        // Loop endpoints & add to endpoint groups
        foreach ($endPoints as $key => $endPoint) {
            $parent = $endPoint['parent'];

            if (empty($parent)) {
                $loneEndpoints[] = $endPoint;
                continue;
            }

            $groupKey = $endPointGroupMap[$parent];

            if (!isset($groupKey)) {
                continue;
            }

            $group = &$endPointGroups[$groupKey];

            $group['endpoints'] ??= [];
            $group['endpoints'][] = $endPoint;
        }

        // Loop endpoint groups & add to their group parents
        // This table should be sorted with the endpoint group's parent below them.
        // The endpoint group with no parent will always be at the bottom, so they are added to the final endpoints.
        foreach ($endPointGroups as $key => $endPointGroup) {
            $parent = $endPointGroup['parent'];

            if (empty($parent)) {
                $finalEndpoints[] = $endPointGroup;
                continue;
            }

            $groupKey = $endPointGroupMap[$parent];

            if (!isset($groupKey)) {
                continue;
            }

            $group = &$endPointGroups[$groupKey];

            if ($group == $endPointGroup) {
                continue;
            }

            $group['endpointGroups'] ??= [];
            $group['endpointGroups'][] = $endPointGroup;
        }

        // Build endpoints
        $this->buildRouteEndpoints($loneEndpoints);

        // Build endpoint groups & their children
        foreach ($finalEndpoints as $key => $group) {
            $this->buildRouteGroups($group);
        }
    }

    /**
     * Resolves all endpoints
     *
     * @param callable $fn
     */
    private function loop(callable $fn): void {
        $searchPath = Util::combinePaths([DUPPY_PATH, "src", $this->endpointsSrc]);
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
            $class = 'Duppy\\' . str_replace("/", "\\", $path);

            $isEndpoint = is_subclass_of($class, 'Duppy\Abstracts\AbstractEndpoint');
            $isEndpointGroup = is_subclass_of($class, 'Duppy\Abstracts\AbstractEndpointGroup');

            if (!$isEndpoint && !$isEndpointGroup) {
                continue;
            }

            $uri = $class::getUri();

            if ($uri == null) {
                $uri = [ $this->resolveUri($path) ];
                $class::$uri = $uri;
            }

            if (!is_array($uri) && $isEndpoint) {
                $uri = [ $uri ];
            }

            $fn([
                'class' => $class,
                'uri' => $uri,
                'type' => $isEndpoint ? 'endpoint' : 'group',
            ]);

            $uri = null;
        }
    }

    /**
     * Resolves the endpoint URI from the class path
     *
     * @param string $path
     * @return string
     */
    private function resolveUri(string $path): string {
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
    private function parseVariables(string $uri): string {
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

    /**
     * Recursively called function for each group to create their child's routes
     *
     * @param array $group
     * @param App|RouteCollectorProxy $app
     */
    private function buildRouteGroups(array $group, $app = null) {
        if ($app == null) {
            $app = Bootstrapper::getApp();
        }

        $grClass = $group['class'];

        $endpoints = $group['endpoints'] ?? [];
        $groups = $group['endpointGroups'] ?? [];

        $this->uriPrefix ??= '';

        $router = $this;
        $path = Util::combinePath($this->uriPrefix, $group['uri']);

        $appGroup = $app->group($path, function(RouteCollectorProxy $group) use($endpoints, $groups, $router) {
            $router->buildRouteEndpoints($endpoints, $group);

            foreach ($groups as $key => $endpointGroup) {
                $router->buildRouteGroups($endpointGroup, $group);
            }
        });

        $grMiddleware = $grClass::getMiddleware();

        foreach ($grMiddleware as $ware) {
            $appGroup->add(new $ware);
        }
    }

    /**
     * Create routes
     *
     * @param array $endpoints
     * @param App|RouteCollectorProxy $app
     */
    private function buildRouteEndpoints(array $endpoints, $app = null) {
        if ($app == null) {
            $app = Bootstrapper::getApp();
        }

        $this->uriPrefix ??= '';

        foreach ($endpoints as $key => $endpoint) {
            $epClass = $endpoint['class'];

            $types = $epClass::getTypes();
            $uris = $epClass::getUri();
            $epMiddleware = $epClass::getMiddleware();
            $epUriFuncMap = $epClass::getUriFuncMap();
            $epUriRedirects = $epClass::getUriRedirectMap();
            $epUriMapTypes = $epClass::getUriMapTypes();

            foreach ($uris as $k => $uri) {
                $uri = Util::combinePath($this->uriPrefix, $uri);

                if (!empty($epUriRedirects) && $epUriRedirects[$k]) {
                    $redirectTo = $epUriRedirects[$k];
                    $responseCode = 302;

                    if (is_array($redirectTo)) {
                        $responseCode = $redirectTo[1] ?? $responseCode;
                        $redirectTo = $redirectTo[0];
                    }

                    // If we need more of these other places we should make a system for them
                    $envVar = "%env:";

                    if (strpos($redirectTo, $envVar) !== false) {
                        $arg = substr($redirectTo, strlen($envVar));
                        $redirectTo = getenv($arg);
                    }

                    $app->redirect($uri, $redirectTo, $responseCode);
                    continue;
                }

                $singleType = count($types) == 1;

                foreach ($types as $type) {
                    $suffix = '';
                    $typeLower = strtolower($type);

                    if (!$singleType) {
                        $suffix .= strtolower($typeLower);
                    }

                    if (!empty($epUriFuncMap) && !empty($epUriFuncMap[$k])) {
                        $suffix .= $epUriFuncMap[$k];
                    }

                    $suffix = lcfirst($suffix); // camelCase;
                    $usedFunc = strlen($suffix) > 0;

                    if ($usedFunc) {
                        $suffix = ":" . $suffix;
                    }

                    $funcName = $epClass . $suffix;

                    // Replacement is needed because function is referenced this way in slim but NOT PHP
                    if ($usedFunc && !method_exists($epClass, substr($suffix, 1))) {
                        continue;
                    }

                    $theseTypesMapped = false;

                    if (!$singleType && !empty($epUriMapTypes) && $epUriMapTypes[$k]) {
                        $route = $app->map($types, $uri, $funcName);
                        $theseTypesMapped = true;
                    } else {
                        $route = $app->$typeLower($uri, $funcName);
                    }

                    foreach ($epMiddleware as $ware) {
                        $route->add(new $ware);
                    }

                    if ($theseTypesMapped) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * Function that recurses over $toBeSorted and sorts the children based on the parent key in each.
     * Returns result backwards, the last value being orphan children ):
     *
     * @param array $toBeSorted
     * @param string|null $parent
     * @param int $depth
     * @param array $result
     * @return array
     */
    private static function sortByParentLoadOrder(array &$toBeSorted, string $parent = null, int &$depth = 0, array &$result = []) {
        if (count($toBeSorted) <= 1) {
            return $toBeSorted;
        }

        foreach ($toBeSorted as $key => $value) {
            if ($value['parent'] != $parent) {
                continue;
            }

            $value['depth'] = $depth;
            //$result[] = $value;
            array_unshift($result, $value);

            $toBeSorted[$key] = null;

            $depth++;
            self::sortByParentLoadOrder($toBeSorted, $value['name'], $depth, $result);
            $depth--;
        }

        return $result;
    }

}
