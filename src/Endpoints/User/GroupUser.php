<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Endpoints\User;

use Duppy\Abstracts\AbstractEndpointGroup;

class GroupUser extends AbstractEndpointGroup {

    /**
     * Set Endpoint to /user/{id}
     *
     * @var ?string
     */
    public static ?string $uri = "/user/{id}";

}
