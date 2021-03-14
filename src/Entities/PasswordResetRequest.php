<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Entities;

use DateTime;

/**
 * PasswordResetRequest Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="user_password_reset")
 */
class PasswordResetRequest {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="WebUser")
     */
    protected WebUser $user;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @ORM\Version
     */
    protected DateTime $time;

    /**
     * Unique code required from email
     * @ORM\Column(type="string", nullable=false)
     */
    protected string $code;

    /**
     * @param WebUser $user
     */
    public function setUser(WebUser $user) {
        $this->user = $user;
    }

    /**
     * Sets the Unique code
     * @param string $code
     */
    public function setCode(string $code) {
        $this->code = $code;
    }

    // Each entity class needs their own version of this function so that doctrine knows to use it for lazy-loading
    /**
     * Return a property
     *
     * @param string $property
     * @return mixed
     */
    #[Pure]
    public function get(string $property): mixed {
        return $this->$property;
    }

}