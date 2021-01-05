<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Entities;

use DateTime;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\Mapping as ORM;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\DuppyServices\UserService;

/**
 * WebUser Verification is a list of unverified users waiting to verify their emails.
 *
 * @ORM\Entity
 * @ORM\Table(name="web_users_verification")
 */
class WebUserVerification {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\Column(type="string")
     */
    protected string $email;

    /**
     * @ORM\Column(type="string")
     */
    protected string $password;

    /**
     * @ORM\Column(type="integer")
     */
    public int $code;

    /**
     * Verification issue date, this is used to expire the record
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ORM\Version
     */
    protected DateTime $issued;

    // Each entity class needs their own version of this function so that doctrine knows to use it for lazy-loading
    /**
     * Return a property
     *
     * @param string $property
     * @return mixed
     */
    public function get(string $property): mixed {
        return $this->$property;
    }

    public function setEmail(string $email) {
        $this->email = $email;
    }

    public function setPassword(string $password) {
        $this->password = $password;
    }

    /**
     * Generates the code for this entity and returns if it is successful
     *
     * @return int|bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function genCode(): int|bool {
        $container = Bootstrapper::getContainer();
        $dbo = $container->get("database");
        $repo = $dbo->getRepository("Duppy\Entities\WebUserVerification");

        $checker = function($int) use($repo) {
            $ct = $repo->count([ 'code' => $int, ]);
            return $ct < 1;
        };

        $code = (new UserService)->inst()->generateUniqueTempCode($checker);

        if ($code == null) {
            return false;
        }

        $this->code = $code;
        return $code;
    }

}