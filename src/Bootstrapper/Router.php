<?php
namespace Duppy\Bootstrapper;

use Slim\App;

final class Router
{
    /**
     * Slim instance
     *
     * @var App|null
     */
    public App $app;

    /**
     * Router constructor.
     */
    public function __construct()
    {
        // Get slim instance
        $this->app = Bootstrapper::getApp();
    }

    public function build(): void
    {

    }
}