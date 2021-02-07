<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Entities;

use DateInterval;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Duppy\DuppyServices\EnvironmentService;
use JetBrains\PhpStorm\Pure;

/**
 * Ban Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="bans")
 */
class Ban {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Environment")
     */
    protected ?Environment $environment = null;

    /**
     * @ORM\ManyToOne(targetEntity="WebUser", inversedBy="bans")
     */
    protected WebUser $user;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @ORM\Version
     */
    protected DateTime $time;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $expiry = null;

    /**
     * Returns if the ban is valid in this environment
     * @return bool
     */
    #[Pure]
    public function inThisEnvironment(): bool {
        $environment = (new EnvironmentService)->inst()->getEnvironment();

        if ($environment == null) {
            return true;
        }

        return $environment === $this->environment;
    }

    /**
     * Returns if the ban is active and not expired
     *
     * @return bool
     */
    public function isActive(): bool {
        $now = new DateTime;
        return $now > $this->time && $this->expired();
    }

    /**
     * Returns if the ban has expired
     *
     * @return bool
     */
    public function expired(): bool {
        if ($this->expiry == null) {
            return false;
        }

        $now = new DateTime;
        return $now >= $this->expiry;
    }

    /**
     * @param Environment $environment
     */
    public function setEnvironment(Environment $environment) {
        $this->environment = $environment;
    }

    /**
     * @param WebUser $user
     */
    public function setUser(WebUser $user) {
        $this->user = $user;
    }

    /**
     * @param DateTime $time
     */
    public function setTime(DateTime $time) {
        $this->time = $time;
    }

    /**
     * @param DateTime $expiry
     */
    public function setExpiry(DateTime $expiry) {
        $this->expiry = $expiry;
    }

    /**
     * @param DateInterval $interval
     */
    public function setExpireFromNow(DateInterval $interval) {
        $now = new DateTime;
        $expire = $now->add($interval);

        $this->setExpiry($expire);
    }

    /**
     * Returns true if the ban's environment is null (global ban)
     *
     * @return bool
     */
    public function isGlobal(): bool {
        return $this->environment == null;
    }

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