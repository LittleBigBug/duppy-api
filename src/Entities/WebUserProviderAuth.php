<?php
namespace Duppy\Entities;

use Doctrine\ORM\Mapping as ORM;
use Duppy\Abstracts\AbstractEntity;

/**
 * WebUserProviderAuth Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="web_users_provider_authmap")
 */
class WebUserProviderAuth extends AbstractEntity {

    /**
     * @ORM\Column(type="int")
     */
    protected int $userid;

    /**
     * @ORM\Column(type="string")
     */
    protected string $providername;

    /**
     * @ORM\Column(type="string")
     */
    protected string $providerid;

    /**
     * @ORM\ManyToOne(targetEntity="WebUser", inversedBy="posts")
     * @ORM\JoinColumn(name="userid", referencedColumnName="id")
     */
    protected $webuser;

}
