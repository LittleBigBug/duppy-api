<?php
namespace Duppy\Entities;

use Duppy\Abstracts\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Setting Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="settings")
 */
class Setting extends AbstractEntity {

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=255)
     */
    protected string $settingKey;

    /**
     * @ORM\Column(type="string")
     */
    protected string $value;

}
