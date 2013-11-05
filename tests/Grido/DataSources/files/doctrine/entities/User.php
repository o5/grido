<?php

namespace Grido\Tests\Entities;

use Nette\Object;
use Doctrine\ORM\Mapping as ORM;

/**
 * User entity.
 *
 * @package     Entities
 * @author      Josef Kříž <pepakriz@gmail.com>
 *
 * @ORM\Entity
 * @ORM\Table(name="user")
 */
class User extends Object
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /**
     * @var string
     * @ORM\Column(length=10)
     */
    public $gender;

    /**
     * @var string
     * @ORM\Column
     */
    public $firstname;

    /**
     * @var string
     * @ORM\Column
     */
    public $surname;

    /**
     * @var string
     * @ORM\Column
     */
    public $country_code;

    /**
     * @var string
     * @ORM\Column
     */
    public $telephonenumber;

    /**
     * @var string
     * @ORM\Column
     */
    public $centimeters;

    /**
     * @var Country
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumn(name="country_code", referencedColumnName="code")
     */
    public $country;
}
