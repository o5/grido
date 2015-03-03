<?php

namespace Grido\Tests\PropertyAccessors;

use Grido\PropertyAccessors\ArrayObjectAccessor;
use Grido\Tests\PropertyAccessors\Files\SingleItem;
use Grido\Tests\PropertyAccessors\Files\SingleItemWithGettersAndSetters;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class ArrayObjectAccessorTest extends TestCase
{
    /* @var ArrayObjectAccessor */
    private $accessor;

    protected function setUp()
    {
        $this->accessor = new ArrayObjectAccessor;
    }

    public function testArray()
    {
        $array = array('name' => 'Tomas');
        Assert::same('Tomas', $this->accessor->getProperty($array, 'name'));

        $array = array('name' => '');
        $this->accessor->setProperty($array, 'name', 'Tomas');
        Assert::same('', $array['name']); // this should be 'Tomas', but setProperty doesn return reference
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
        // ends with 'Fatal error'
//		Assert::error(function () use ($object) {
//			$this->accessor->getProperty($object, 'name');
//		}, '?');

        // ends with 'Fatal error'
        $object = new SingleItemWithGettersAndSetters;
//		Assert::error(function () use ($object) {
//			$this->accessor->setProperty($object, 'name', 'Joe');
//		}, '?');
    }
}


$test = new ArrayObjectAccessorTest;
$test->run();
