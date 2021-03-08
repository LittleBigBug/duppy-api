<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Endpoints;

use Duppy\Abstracts\AbstractEndpoint;

class Home extends AbstractEndpoint {

    /**
     * Set the URI to /
     *
     * @var ?array
     */
    public static ?array $uri = [ '/' ];

    public static ?array $uriRedirect = [ ["%stg:clientUrl", 302] ];

}
