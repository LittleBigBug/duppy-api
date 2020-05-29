<?php
namespace Duppy\Mods\Forums\Entities;

use Ramsey\Uuid\Doctrine\UuidGenerator;
use Duppy\Abstracts\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * Category Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="categories")
 */
class Category extends AbstractEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     */
    protected UuidInterface $uuid;

    /**
     * @ORM\Column(type="string", length=16)
     */
    protected string $slug;

    /**
     * @ORM\Column(type="smallint")
     */
    protected int $order_num;

    /**
     * @ORM\Column(type="string", length=32)
     */
    protected string $title;

    /**
     * @ORM\OneToMany(targetEntity="Thread", mappedBy="category")
     */
    protected $threads;
}