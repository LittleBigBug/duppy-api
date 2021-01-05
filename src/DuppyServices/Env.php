<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\DuppyServices;

use Dotenv\Dotenv;
use Duppy\Abstracts\AbstractService;
use JetBrains\PhpStorm\Pure;

/**
 * Env Service allows tests to manipulate env variables
 *
 * Class Env
 * @package Duppy\DuppyServices
 */
class Env extends AbstractService {

    public Dotenv $dotEnv;

    /**
     * Static alias allowed to be called anywhere and redirects to (possibly mocked) service
     *
     * @param string $key
     * @return bool|array|string
     */
    public static function G(string $key): bool|array|string {
        return (new Env)->inst()->realGet($key);
    }

    /**
     * Easier functions allowed to be called anywhere and redirects to (possibly mocked) service
     *
     * @param string $key
     * @return bool|array|string
     */
    public function Get(string $key): bool|array|string {
        return $this->inst()->realGet($key);
    }

    /**
     * Starts app dotEnv
     * @return Dotenv
     */
    public function start(): Dotenv {
        $this->dotEnv = Dotenv::createMutable(DUPPY_PATH);

        $this->dotEnv->required([
            'JWT_SECRET',
            'CLIENT_URL',
        ]);

        $this->dotEnv->load();
        return $this->dotEnv;
    }

    /**
     * Function to be overridden in case of testing
     *
     * @param string $key
     * @return bool|array|string
     */
    #[Pure]
    public function realGet(string $key): bool|array|string {
        return getenv($key);
    }

}