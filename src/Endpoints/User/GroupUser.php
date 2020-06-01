<?php
namespace Duppy\Endpoints\User;

use Duppy\Abstracts\AbstractEndpointGroup;

// This is it.. this is all of the class. This could probably be done better.
// Maybe replace with https://stitcher.io/blog/new-in-php-8#attributes-rfc in the future

class GroupUser extends AbstractEndpointGroup
{

    /**
     * Set Endpoint to /user/{id}
     *
     * @var string
     */
    public static ?string $uri = "/user/{id}";


}
