<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Builders;

use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Abstracts\AbstractEndpointGroup;
use Duppy\Abstracts\AbstractFileBuilder;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Util;
use JetBrains\PhpStorm\Pure;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/**
 * Build app endpoints and endpoint groups
 *
 * Class Router
 * @package Duppy\Builders
 */
final class Router extends AbstractFileBuilder {

    /**
     * Map of routes
     *
     * @var array
     */
    public array $routes = [];

    /**
     * Prefix to put before all URIs in this Router
     *
     * @var string
     */
    public string $uriPrefix = "";

    /**
     * Router constructor.
     *
     * @param string $uriPrefix
     * @param string $endpointsSrc
     */
    #[Pure]
    public function __construct(string $uriPrefix = "", string $endpointsSrc = "Endpoints") {
        $this->uriPrefix = $uriPrefix;
        parent::__construct($endpointsSrc);
    }

    /**
     * @param mixed $obj
     * @return bool
     */
    #[Pure]
    public function isEndpointGroup(mixed $obj): bool {
        $epClass = AbstractEndpointGroup::class;
        return is_subclass_of($obj, $epClass);
    }

    /**
     * @param mixed $obj
     * @return bool
     */
    #[Pure]
    public function isEndpoint(mixed $obj): bool {
        $epClass = AbstractEndpoint::class;
        return is_subclass_of($obj, $epClass);
    }

    /**
     * Build slim routes
     */
    public function build() {
        $endPoints = [];
        $endPointGroups = [];

        $callback = function (string $class, string $path) use (&$endPoints, &$endPointGroups) {
            $uri = $class::getUri();

            if ($uri == null) {
                $uri = [ $this->resolveUri($path) ];
                $class::setUri($uri);
            }

            $isEndpoint = $this->isEndpoint($class);

            if (!is_array($uri) && $isEndpoint) {
                $uri = [ $uri ];
            }

            $parent = $class::getParentGroupEndpoint();

            $endpoint = [
                "class" => $class,
                "uri" => $uri,
                "type" => $isEndpoint ? "endpoint" : "group",
                "parent" => $parent,
            ];

            switch ($endpoint['type']) {
                case 'endpoint':
                    $endPoints[] = $endpoint;
                    break;
                case 'group':
                    $endPointGroups[] = $endpoint;
                    break;
            }

            $uri = null;
        };

        $filter = function (string $className, string $path): bool {
            return $this->isEndpoint($className) || $this->isEndpointGroup($className);
        };

        $this->directoryIterator(true, $callback, $filter);

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
     * Resolves the endpoint URI from the class path
     *
     * @param string $path
     * @return string
     */
    private function resolveUri(string $path): string {
        $path = substr($path, strlen($this->buildSrc));
        $uri = str_replace("\\", "/", $path);

        // Add dashes between words
        $uri = preg_replace("/(?<![_])\B([A-Z])/", "-$1", $uri);

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
            if (str_starts_with($string, '_')) {
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
     * @param App|RouteCollectorProxy|null $app
     */
    private function buildRouteGroups(array $group, App|RouteCollectorProxy $app = null) {
        if ($app == null) {
            $app = Bootstrapper::getApp();
        }

        $grClass = $group["class"];

        $endpoints = $group["endpoints"] ?? [];
        $groups = $group["endpointGroups"] ?? [];

        $this->uriPrefix ??= "";

        $router = $this;
        $path = Util::combinePaths([
            DUPPY_URI_PATH,
            $this->uriPrefix, $group["uri"],
        ]);

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
     * @param App|RouteCollectorProxy|null $app
     */
    private function buildRouteEndpoints(array $endpoints, App|RouteCollectorProxy $app = null) {
        if ($app == null) {
            $app = Bootstrapper::getApp();
        }

        $this->uriPrefix ??= '';

        foreach ($endpoints as $key => $endpoint) {
            $epClass = $endpoint["class"];

            $types = $epClass::getTypes();
            $uris = $epClass::getUri();
            $epMiddleware = $epClass::getMiddleware();
            $epUriFuncMap = $epClass::getUriFuncMap();
            $epUriRedirects = $epClass::getUriRedirectMap();
            $epUriMapTypes = $epClass::getUriMapTypes();
            $epParent = $epClass::getParentGroupEndpoint();

            $hasParent = is_subclass_of($epParent, 'Duppy\Abstracts\AbstractEndpointGroup');

            foreach ($uris as $k => $uri) {
                // Only apply these paths when outside of a group
                if (!$hasParent) {
                    $uri = Util::combinePaths([
                        DUPPY_URI_PATH,
                        $this->uriPrefix, $uri,
                    ]);
                }

                if (!empty($epUriRedirects) && $epUriRedirects[$k]) {
                    $redirectTo = $epUriRedirects[$k];
                    $responseCode = 302;

                    if (is_array($redirectTo)) {
                        $responseCode = $redirectTo[1] ?? $responseCode;
                        $redirectTo = $redirectTo[0];
                    }

                    // If we need more of these other places we should make a system for them
                    $envVar = "%env:";

                    if (str_contains($redirectTo, $envVar)) {
                        $arg = substr($redirectTo, strlen($envVar));
                        $redirectTo = getenv($arg);
                    }

                    $app->redirect($uri, $redirectTo, $responseCode);
                    continue;
                }

                $singleType = count($types) == 1;
                $shouldMap = !$singleType && (is_array($epUriMapTypes) && !empty($epUriMapTypes) && $epUriMapTypes[$k]) || $epUriMapTypes === true;

                foreach ($types as $type) {
                    $suffix = '';
                    $typeLower = strtolower($type);

                    if ($shouldMap) {
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
                    if ($usedFunc && !$shouldMap && !method_exists($epClass, substr($suffix, 1))) {
                        continue;
                    }

                    $theseTypesMapped = false;

                    if ($shouldMap) {
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
    private static function sortByParentLoadOrder(array &$toBeSorted, string $parent = null, int &$depth = 0, array &$result = []): array {
        if (count($toBeSorted) <= 1) {
            return $toBeSorted;
        }

        foreach ($toBeSorted as $key => $value) {
            if (!is_array($value) || $value["parent"] != $parent) {
                continue;
            }

            $value["depth"] = $depth;
            //$result[] = $value;
            array_unshift($result, $value);

            $toBeSorted[$key] = null;

            $depth++;
            self::sortByParentLoadOrder($toBeSorted, $value["parent"], $depth, $result);
            $depth--;
        }

        return $result;
    }

}
