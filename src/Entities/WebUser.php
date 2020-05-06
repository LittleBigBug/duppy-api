<?php
namespace Duppy\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * WebUser Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="web_user")
 */
class WebUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length="17")
     */
    protected $steamid64;

    /**
     * @ORM\Column(type="string")
     */
    protected $username;

    /**
     * @ORM\Column(type="string", nullable="true")
     */
    protected $bio;

    /**
     * @ORM\Column(type="string")
     */
    protected $email;

    /**
     * Returns steamid64
     *
     * @return string
     */
    public function getSteamid64(): string
    {
        return $this->steamid64;
    }

    /**
     * Returns username
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Returns bio
     *
     * @return string
     */
    public function getBio(): string
    {
        return $this->bio;
    }

    /**
     * Returns email
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Sets steamid
     *
     * @param $steamid64
     * @return $this
     */
    public function setSteamid64($steamid64): WebUser
    {
        $this->steamid64 = $steamid64;
        return $this;
    }

    /**
     * Sets username
     *
     * @param $steamid64
     * @return $this
     */
    public function setUsername($username): WebUser
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Sets bio
     *
     * @param $steamid64
     * @return $this
     */
    public function setBio($bio): WebUser
    {
        $this->bio = $bio;
        return $this;
    }

    /**
     * Sets email
     *
     * @param $steamid64
     * @return $this
     */
    public function setEmail($email): WebUser
    {
        $this->email = $email;
        return $this;
    }
}