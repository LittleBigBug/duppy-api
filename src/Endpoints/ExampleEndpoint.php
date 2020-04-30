<?php
namespace Duppy\Endpoints;

use Duppy\Abstracts\AbstractEndpoint;

class ExampleEndpoint extends AbstractEndpoint
{
    /**
     * Type of request
     *
     * @var string
     */
    public string $type = 'get';
}