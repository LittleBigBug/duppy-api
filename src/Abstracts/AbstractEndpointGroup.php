<?php
namespace Duppy\Abstracts;

abstract class AbstractEndpointGroup {

    /**
     * Endpoint URI (defaults to path)
     *
     * @var string|null
     */
    public static ?string $uri = null;

    /**
     * Route middleware
     *
     * @var array
     */
    public static array $middleware = [];

    /**
     * Endpoint's group parent
     * All $uri s will be relative to parent(s)
     * Classname of a class that inherits the abstract class AbstractEndpointGroup
     *
     * @var string|null
     */
    public static ?string $parentGroup = null;

    /**
     * Returns uri
     *
     * @return string|null
     */
    final public static function getUri(): ?string {
        return static::$uri;
    }

    /**
     * Returns the middleware for the endpoint group
     *
     * @return array
     */
    final public static function getMiddleware(): array {
        return static::$middleware;
    }

    /**
     * Returns the endpoint parent group classname (AbstractEndpointGroup)
     *
     * @return string|null
     */
    final public static function getParentGroupEndpoint(): ?string {
        return static::$parentGroup;
    }

}
