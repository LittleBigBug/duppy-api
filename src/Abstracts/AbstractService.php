<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Abstracts;

use Exception;

class AbstractService {

    /**
     * Used for tests
     *
     * @var array
     */
    public array $dynFunctionsMap = [];

    /**
     * @var array
     */
    public static array $singletons = [];

    /**
     * AbstractService constructor.
     * @param bool $singleton
     */
    public function __construct(bool $singleton = false) {
        static::SetSingleton($this);
    }

    /**
     * @return AbstractService
     */
    public function inst(): AbstractService {
        return static::Singleton($this::class);
    }

    /**
     * Magic call missing functions primarily used for testing
     *
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call(string $name, array $args): mixed {
        if (array_key_exists($name, $this->dynFunctionsMap)) {
            $func = $this->dynFunctionsMap[$name];
            return $func(...$args);
        }

        return null;
    }

    /**
     * @param string|null $name
     * @return $this
     */
    public static function Singleton(?string $name = null): AbstractService {
        if (empty($name)) {
            $name = get_called_class();
        }

        $service = new $name;
        AbstractService::SetSingleton($service);

        return static::$singletons[$name] ?? $service;
    }

    /**
     * @param AbstractService $service
     */
    public static function SetSingleton(AbstractService $service) {
        static::$singletons[$service::class] = $service;
    }

    /**
     * Mock service
     *
     * @param string $serviceName
     * @param AbstractService $mockService
     */
    public static function MockService(string $serviceName, AbstractService $mockService) {
       static::$singletons[$serviceName] = $mockService;
    }

    /**
     * @param string $key
     * @param $function
     */
    public function addFunction(string $key, $function) {
        $this->dynFunctionsMap[$key] = $function;
    }

}