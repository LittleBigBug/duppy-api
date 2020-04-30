<?php
namespace Duppy\Abstracts;

abstract class AbstractEndpoint
{
    /**
     * Type of request
     *
     * @var string
     */
    public string $type;

    /**
     * Returns type of request
     *
     * @return string
     */
    final public function getType(): string
    {
        return $this->type;
    }
}