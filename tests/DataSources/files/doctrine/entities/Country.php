<?php

namespace Grido\Tests\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Country entity.
 *
 * @package     Entities
 * @author      Josef Kříž <pepakriz@gmail.com>
 *
 * @ORM\Entity
 * @ORM\Table(name="country")
 */
class Country
{
    use \Nette\SmartObject;

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(length=2)
     */
    public $code;

    /**
     * @var string
     * @ORM\Column()
     */
    public $title;

    /**
     * @var ArrayCollection|User[]
     * @ORM\OneToMany(targetEntity="User", mappedBy="country")
     */
    public $users;


    public function __toString()
    {
        return $this->title;
    }
}
