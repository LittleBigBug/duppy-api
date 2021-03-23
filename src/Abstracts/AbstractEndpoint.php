<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Abstracts;

use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Duppy\Bootstrapper\Bootstrapper;
use DI\Container;

abstract class AbstractEndpoint {
    /**
     * Type(s) of requests accepted
     *
     * @var string[]
     */
    public static array $types = [ 'GET' ];

    /**
     * Endpoint URI(s) (defaults to path)
     *
     * @var string[]|null
     */
    public static ?array $uri = null;

    /**
     * Endpoint URI function names map (not needed if $uri contains < 2)
     * Keys of $uri must match
     *
     * Please use PascalCase. The function will be turned into camelCase appropriately
     *
     * @var string[]|null
     */
    public static ?array $uriFuncNames = null;

    /**
     * Endpoint URI redirect map
     * Keys of $uri must match. Not needed.
     *
     * Can be an array of strings (where to redirect)
     * or an array, with the first key is where to redirect and the second is the HTTP Code
     * (by default, 302)
     *
     * @var array|null
     */
    public static ?array $uriRedirect = null;

    /**
     * Either a boolean or an array of booleans (where keys match with $uri)
     * Specifies if seperate functions for $types should be called instead.
     * This doesn't apply if $types only has one member.
     *
     * @var array|boolean
     */
    public static array|bool $uriMapTypes = false;

    /**
     * Route middleware
     *
     * @var array
     */
    public static array $middleware = [];

    /**
     * Mapped route middleware (index matches each route)
     *
     * @var array
     */
    public static array $mappedMiddleware = [];

    /**
     * Endpoint's group parent
     * All $uri s will be relative to parent(s)
     * Classname of a class that inherits the abstract class AbstractEndpointGroup
     *
     * @var string|null
     */
    public static ?string $parentGroup = null;

    /**
     * @param bool $force
     */
    public static function clear(bool $force = false) { }

    /**
     * Handles the response
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response {
        return $response;
    }

    /**
     * Sets uri(s)
     *
     * @param array|string|null
     */
    final public static function setUri(array|string|null $newUris) {
        if (!is_array($newUris)) {
            $newUris = [ $newUris ];
        }

        static::$uri = $newUris;
    }

    /**
     * Returns type(s) of requests accepted
     *
     * @return array
     */
    final public static function getTypes(): array {
        return static::$types;
    }

    /**
     * Returns uri(s)
     *
     * @return array|null
     */
    final public static function getUri(): ?array {
        return static::$uri;
    }

    /**
     * Returns the uri function name map
     *
     * @return array|null
     */
    final public static function getUriFuncMap(): ?array {
        return static::$uriFuncNames;
    }

    /**
     * Returns the uri redirect map
     *
     * @return array|null
     */
    final public static function getUriRedirectMap(): ?array {
        return static::$uriRedirect;
    }

    /**
     * Returns the uri map types
     *
     * @return array|boolean|null
     */
    final public static function getUriMapTypes(): array|bool|null {
        return static::$uriMapTypes;
    }

    /**
     * Returns the middleware for the endpoint
     *
     * @return array
     */
    final public static function getMiddleware(): array {
        return static::$middleware;
    }

    /**
     * Returns endpoint mapped middleware
     *
     * @return array
     */
    final public static function getMappedMiddleware(): array {
        return static::$mappedMiddleware;
    }

    /**
     * Returns the endpoint parent group class name (AbstractEndpointGroup)
     *
     * @return string|null
     */
    final public static function getParentGroupEndpoint(): ?string {
        return static::$parentGroup;
    }

    /**
     * Returns dependency container instance
     *
     * @return Container
     */
    final public static function getContainer(): Container {
        return Bootstrapper::getContainer();
    }
}
