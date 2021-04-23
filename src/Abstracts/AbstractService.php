<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Abstracts;

/**
 * Service classes are objects that are handled as singletons.
 * but allow for tests to mock them and implement different functions
 *
 * $obj = new MyService;
 * $obj->Singleton(); // Creates if non-existant and returns the singleton
 * $obj->inst(); // Alias for above
 *
 * (new MyService)->inst()->aFunction();
 *
 * // Force a singleton to be recreated
 * $obj = new MyService(true);
 * $obj->aFunction();
 *
 * When writing Services, in order to increase testability, code that you want tested that access functions within
 * itself, it's a good idea to use the above method to access the singleton.
 *
 * This way, the test can still mock the singleton and the (ex: database) specific functions within while still being able to
 * instantiate a local service to run the code to test.
 *
 * Class AbstractService
 * @package Duppy\Abstracts
 */
class AbstractService {

    /**
     * Used for tests
     *
     * @var array
     */
    public array $dynFunctionsMap = [];

    /**
     * @var AbstractService[]
     */
    public static array $singletons = [];

    /**
     * AbstractService constructor.
     * @param bool $singleton
     */
    public function __construct(bool $singleton = false) {
        // Set itself to be the new singleton if its specified
        if ($singleton) {
            static::SetSingleton($this);
        }
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
     * Cleanup function called on all services in the middleware to clean up variables or do other things on exit
     *
     * @param bool $force
     */
    public function clean(bool $force = false) { }

    /**
     * Shorter function
     *
     * @return AbstractService
     */
    public final function inst(): AbstractService {
        return $this->Singleton($this::class);
    }

    /**
     * @param bool $useNew = false  If 'false' and the singleton is missing, the current object will become the singleton
     * @return $this
     */
    public final function Singleton(bool $useNew = false): AbstractService {
        $name = $this::class;

        // Create a new service if the singleton doesn't exist
        if (!array_key_exists($name, AbstractService::$singletons) || ($ret = AbstractService::$singletons[$name]) == null) {
            $use = $this;

            if ($useNew) {
                $use = new $name;
            }

            AbstractService::SetSingleton($use);
            $ret = $use;
        }

        return $ret;
    }

    /**
     * Cleans all services registered as a singleton
     * @param bool $force
     */
    public static function CleanServices(bool $force = false) {
        foreach (AbstractService::$singletons as $singleton) {
            $singleton->clean($force);
        }
    }

    /**
     * @param string|null $name
     * @return AbstractService
     */
    public static function StaticSingleton(?string $name = null): AbstractService {
        if (empty($name)) {
            $name = get_called_class();
        }

        $ret = new $name;
        $ret->Singleton();

        // Create a new service if the singleton doesn't exist
        if (!array_key_exists($name, AbstractService::$singletons) || AbstractService::$singletons[$name] == null) {
            AbstractService::SetSingleton($ret);
        }

        return $ret;
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