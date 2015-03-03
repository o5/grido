<?php

namespace Grido\Tests\PropertyAccessors;

use Grido\PropertyAccessors\SymfonyPropertyAccessor;
use Grido\Tests\PropertyAccessors\Files\SingleItem;
use Grido\Tests\PropertyAccessors\Files\SingleItemWithGettersAndSetters;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class SymfonyPropertyAccessorTest extends TestCase
{
    /* @var SymfonyPropertyAccessor */
    private $accessor;

    protected function setUp()
    {
        $this->accessor = new SymfonyPropertyAccessor;
    }

    public function testArray()
    {
        $array = array('name' => 'Tomas');
        Assert::same('Tomas', $this->accessor->getProperty($array, 'name'));

        $array = array('name' => '');
        $this->accessor->setProperty($array, 'name', 'Tomas');
        Assert::same('', $array['name']); // this should be 'Tomas', but setProperty doesn't return reference in array
    }

    public function testObjectWithPublicProperty()
    {
        $object = new SingleItem;
        $object->name = 'John';
        Assert::same('John', $this->accessor->getProperty($object, 'name'));

        $object = new SingleItem;
        $this->accessor->setProperty($object, 'name', 'John');
        Assert::same('John', $object->name);
    }

    public function testObjectWithGettersAndSetters()
    {
        $object = new SingleItemWithGettersAndSetters('Joe');
        Assert::same('Joe', $this->accessor->getProperty($object, 'name'));

        $object = new SingleItemWithGettersAndSetters;
        $this->accessor->setProperty($object, 'name', 'Joe');
        Assert::same('Joe', $this->accessor->getProperty($object, 'name'));
    }
}


$test = new SymfonyPropertyAccessorTest;
$test->run();
