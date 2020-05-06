<?php
namespace Duppy\Entities;

use Ramsey\Uuid\Doctrine\UuidGenerator;
use Duppy\Abstracts\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;

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
    protected $uuid;

    /**
     * @ORM\Column(type="string", length=16)
     */
    protected string $slug;

    /**
     * @ORM\Column(type="smallint")
     */
    protected int $order;

    /**
     * @ORM\Column(type="string", length=32)
     */
    protected string $title;
}