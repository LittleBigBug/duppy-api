<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Environment Entity
 *
 * 'Environments' can allow the API to run separate configurations and permissions on the same API
 *
 * For example, this could be used to create a forum website like Enjin, where one software (enjin) would share users
 * but could have separate groups, permissions, themes, posts, etc on the same platform.
 *
 * This was created however for the dreamin.gg servers allowing for separate ranks depending on the server. This can
 * also be used to have separate forum categories and permissions for those servers that have those permissions for the
 * moderators ALL IN THE SAME PLACE
 *
 * Groups and assignments can also be specified to bypass these environments, so system admins (or in a shared network,
 * supporters) can have global access across the entire system
 *
 * This can be made optional by just having 1 environment and defaulting to it.
 *
 * @ORM\Entity
 * @ORM\Table(name="environments")
 */
class Environment {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\Column(type="string")
     */
    protected string $name;

    /**
     * @ORM\Column(type="bool")
     */
    protected bool $enabled = true;

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

    public function setName(string $name) {
        $this->name = $name;
    }

    public function setEnabled(bool $enabled) {
        $this->enabled = $enabled;
    }

}