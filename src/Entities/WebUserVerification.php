<?php


namespace Duppy\Entities;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

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
    protected string $username;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected string $email;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected string $password;

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
    public function get(string $property) {
        return $this->$property;
    }

    public function setEmail(string $email) {
        $this->email = $email;
    }

    public function setPassword(string $password) {
        $this->password = $password;
    }

    public function setUsername(string $username) {
        $this->username = $username;
    }

}