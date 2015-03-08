<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\PropertyAccessors;

use Nette\Database\Table\IRow;
use Nette\Utils\Strings;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Symfony property accessor.
 *
 * @package     Grido
 * @subpackage  PropertyAccessors
 * @author      Josef Kříž <pepakriz@gmail.com>
 * @link        http://symfony.com/doc/current/components/property_access/introduction.html
 */
class SymfonyPropertyAccessor
{
    /** @var PropertyAccessor */
    private $propertyAccessor;

    public function __construct()
    {
        $this->propertyAccessor = new PropertyAccessor(TRUE, TRUE);
    }

    /**
     * @param array|object $object
     * @param string $name
     * @return mixed
     */
    public function getProperty($object, $name)
    {
        if ($object instanceof IRow && Strings::contains($name, '.')) {
            $parts = explode('.', $name);
            foreach ($parts as $item) {
                if (is_object($object)) {
                    $object = $object->$item;
                }
            }
            return $object;
        }

        if (is_array($object)) {
            $name = '[' . $name . ']';
        }
        return $this->propertyAccessor->getValue($object, $name);
    }

    /**
     * @param array|object $object
     * @param string $name
     * @param mixed $value
     */
    public function setProperty($object, $name, $value)
    {
        if (is_array($object)) {
            $name = '[' . $name . ']';
        }
        $this->propertyAccessor->setValue($object, $name, $value);
    }
}
