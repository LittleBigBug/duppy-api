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

    /**
     * Return a property
     *
     * @param string $property
     * @return mixed
     */
    final public function get(string $property)
    {
        return $this->$property;
    }
}