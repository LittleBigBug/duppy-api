<?php
namespace Duppy\Abstracts;

abstract class AbstractEntity
{
    /**
     * AbstractEntity constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        foreach ($data as $property => $value) {
            $this->$property = $value;
        }
    }
}