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
use JsonSerializable;

/**
 * Ban Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="bans")
 */
class Ban implements JsonSerializable {

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
     * @ORM\ManyToOne(targetEntity="WebUser", nullable=true)
     */
    protected ?WebUser $banningUser = null;

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
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $reason = null;

    /**
     * @ORM\Column(type="bool", nullable=false)
     */
    protected bool $appealable = true;

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
     * @param WebUser $user
     */
    public function setBanningUser(WebUser $user) {
        $this->banningUser = $user;
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
     * @param string $reason
     */
    public function setReason(string $reason) {
        $this->reason = $reason;
    }

    /**
     * @param bool $appealable
     */
    public function setAppealable(bool $appealable) {
        $this->appealable = $appealable;
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

    /**
     * @return bool
     */
    public function isPermanent(): bool {
        return $this->expiry == null;
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

    /**
     * @return array
     */
    public function jsonSerialize(): array {
        return [
            "id" => $this->id,
            "user" => $this->user,
            "banningUser" => $this->banningUser,
            "time" => $this->time,
            "expiry" => $this->expiry,
            "reason" => $this->reason,
            "environment" => $this->environment,

            // Generated
            "active" => $this->isActive(),
            "global" => $this->isGlobal(),
            "permanent" => $this->isPermanent(),
        ];
    }
}