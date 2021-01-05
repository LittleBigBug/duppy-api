<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Endpoints\Admin;

use Duppy\Abstracts\AbstractEndpointGroup;

class GroupAdmin extends AbstractEndpointGroup {

    /**
     * Set Endpoint to /admin
     *
     * @var ?string
     */
    public static ?string $uri = "/admin";

    /**
     * Admin only middleware
     *
     * @var array
     */
    public static array $middleware = [ "Duppy\Middleware\AdminAccessMiddleware" ];

}