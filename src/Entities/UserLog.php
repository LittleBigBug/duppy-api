<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Entities;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\DuppyException;
use Duppy\DuppyServices\Settings;
use Doctrine\ORM\Mapping as ORM;
use geertw\IpAnonymizer\IpAnonymizer;
use JetBrains\PhpStorm\Pure;

/**
 * UserLog Entity
 * Contains more information about a user associated with a log
 *
 * @ORM\Entity
 * @ORM\Table(name="logs_user")
 */
class UserLog {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * User associated, if any
     *
     * @ORM\ManyToOne(targetEntity="WebUser")
     */
    protected ?WebUser $user = null;

    /**
     * IP Address of the user
     * Anonymized (if enabled)
     *
     * @ORM\Column(type="string")
     */
    protected string $ip = "";

    /**
     * Unique IP Hash of the user (used for IP bans)
     *
     * @ORM\Column(type="string")
     */
    protected string $ipHash = "";

    /**
     * Sets user
     *
     * @param WebUser $user
     */
    public function setUser(WebUser $user) {
        $this->user = $user;
    }

    /**
     * Sets IP and IP hash (based on anonymization)
     *
     * @param string $ip
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    public function setIp(string $ip) {
        $stgService = (new Settings)->inst();
        $anonymize = $stgService->getSetting("anonymizeIPs", true);

        $this->ipHash = hash("sha256", $ip);

        if ($anonymize) {
            $ip = IpAnonymizer::anonymizeIp($ip);
        }

        $this->ip = $ip;
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