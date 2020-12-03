<?php


namespace Duppy\Entities;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Duppy\Abstracts\AbstractEntity;

/**
 * WebUser Verification is a list of unverified users waiting to verify their emails.
 *
 * @ORM\Entity
 * @ORM\Table(name="web_users_verification")
 */
class WebUserVerification extends AbstractEntity {

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

}