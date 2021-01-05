<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Entities;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * WebUserProviderAuth Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="web_user_auth_providers")
 */
class WebUserProviderAuth {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\Column(type="string")
     */
    protected string $providername;

    /**
     * @ORM\Column(type="string")
     */
    protected string $providerid;

    /**
     * @ORM\ManyToOne(targetEntity="WebUser", inversedBy="providerAuths")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected WebUser $user;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @ORM\Version
     */
    protected DateTime $created;

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

    public function setProviderName(string $providerName) {
        $this->providername = $providerName;
    }

    public function setProviderId(string $providerId) {
        $this->providerid = $providerId;
    }

    public function setUser(WebUser $user) {
        $this->user = $user;
    }

}
