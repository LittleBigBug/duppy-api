<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Setting Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="settings")
 */
class Setting {

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=255)
     */
    protected string $settingKey;

    /**
     * @ORM\Column(type="string")
     */
    protected string $value;

    // Each entity class needs their own version of this function so that doctrine knows to use it for lazy-loading
    /**
     * Return a property
     *
     * @param string $property
     * @return mixed
     */
    public function get(string $property) {
        return $this->$property;
    }

}
