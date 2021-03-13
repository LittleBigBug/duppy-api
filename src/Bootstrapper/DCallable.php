<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Bootstrapper;

/**
 * Callable class
 * Represents a Callable
 *
 * Class DCallable
 * @package Duppy\Bootstrapper
 */
class DCallable {

    /**
     * @var callable
     */
    private mixed $callback;

    /**
     * DCallable constructor.
     *
     * @param callable $callback
     */
    public function __construct(callable $callback = null) {
        $this->callback = $callback;
    }

    /**
     * Use magic function to alias invoke
     *
     * @param mixed ...$params
     * @return mixed
     */
    public function __invoke(...$params): mixed {
        return $this->invoke(...$params);
    }

    /**
     * Invoke the callable method (safely)
     *
     * @param mixed ...$params
     * @return mixed
     */
    public function invoke(...$params): mixed {
        if (!is_callable($this->callback)) {
            return null;
        }

        return call_user_func($this->callback, $params);
    }

}