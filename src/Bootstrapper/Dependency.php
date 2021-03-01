<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Bootstrapper;

use DI\Container;

/**
 * Helps manage duppy dependencies in a workers environment
 *
 * Class Dependency
 * @package Duppy\Bootstrapper
 */
class Dependency {

    /**
     * Dependency object
     *
     * @var ?object
     */
    private ?object $object = null;

    /**
     * Dependency create callback
     *
     * @var DCallable
     */
    private DCallable $createCallback;

    /**
     * Should the dependency be re-configured for every new request?
     *
     * @var bool = false
     */
    private bool $perRequestConfig = false;

    /**
     * Registered dependencies
     * @var Dependency[]
     */
    private static array $injectedDependencies = [];

    /**
     * Refresh all registered dependencies
     */
    public static function refreshInjected() {
        foreach (static::$injectedDependencies as $dependency) {
            $dependency->refresh();
        }
    }

    /**
     * Dependency constructor.
     * @param callable $createCallback
     */
    public function __construct(callable $createCallback) {
        $this->createCallback = new DCallable($createCallback);
    }

    /**
     * Configure for each request
     *
     * @param bool $perRequest
     */
    public function setPerRequestConfig(bool $perRequest = true) {
        $this->perRequestConfig = $perRequest;
    }

    /**
     * Set a PHP-DI container to use this dependency's get function
     *
     * @param Container $container
     * @param string $name
     */
    public function inject(Container $container, string $name) {
        $container->set($name, fn () => $this->get());
        $injectedDependencies[$name] = $this;
    }

    /**
     * Generate or get the cached dependency object
     *
     * @return object
     */
    public function get(): mixed {
        return $this->object ?? $this->object = $this->createCallback->invoke();
    }

    /**
     * Refresh dependency cache (if configured to)
     */
    public function refresh() {
        if (!$this->perRequestConfig) {
            return;
        }

        $this->object = null;
    }

}