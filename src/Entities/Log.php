<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Entities;

use DateTime;

/**
 * Log Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="logs")
 */
class Log {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @ORM\Version
     */
    protected DateTime $time;

    /**
     * @ORM\ManyToOne(targetEntity="WebUser")
     */
    protected WebUser $user;

    /**
     * String identifier for logs
     *
     * @ORM\Column(type="string")
     */
    protected string $logNote;

}