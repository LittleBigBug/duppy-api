<?php

namespace Duppy\Entities;

use Doctrine\ORM\Mapping as ORM;
use Duppy\Abstracts\AbstractEntity;

/**
 * UserGroup Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="user_groups")
 */
class UserGroup extends AbstractEntity {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\Column(type="string")
     */
    protected string $name;

    /**
     * @ORM\Column(type="integer")
     */
    protected int $weight;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected string $colour;

    /**
     * @ORM\ManyToMany(targetEntity="WebUser", mappedBy="groups")
     */
    protected $users;

    /**
     * @ORM\OneToMany(targetEntity="UserGroup", mappedBy="parent")
     */
    protected $children;

    /**
     * @ORM\ManyToOne(targetEntity="UserGroup", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parent;

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getWeight() {
        return $this->weight;
    }

    public function getColour() {
        return $this->colour;
    }

    public function getParent() {
        return $this->parent;
    }

}