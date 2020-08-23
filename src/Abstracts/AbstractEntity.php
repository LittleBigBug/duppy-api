<?php
namespace Duppy\Abstracts;

use Doctrine\ORM\Mapping as ORM;

abstract class AbstractEntity {
    /**
     * @ORM\Column(type="datetime", columnDefinition="TIMESTAMP DEFAULT CURRENT_TIMESTAMP")
     */
    protected \DateTime $created_at;

    /**
     * AbstractEntity constructor.
     *
     * @param array $data
     */
    public function __construct(array $data) {
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
    final public function get(string $property) {
        return $this->$property;
    }
}
