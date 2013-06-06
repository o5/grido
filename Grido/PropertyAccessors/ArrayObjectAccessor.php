<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\PropertyAccessors;

/**
 * Accessor for array and object structures.
 *
 * @package     Grido
 * @subpackage  PropertyAccessors
 * @author      Josef Kříž <pepakriz@gmail.com>
 */
class ArrayObjectAccessor implements IPropertyAccessor
{
    /**
     * @param mixed $object
     * @param string $name
     * @return bool
     */
    public static function hasProperty($object, $name)
    {
        if (is_object($object) && $object instanceof \Nette\Database\Table\ActiveRow) {
            //https://github.com/nette/nette/pull/1100
            return array_key_exists($name, $object->toArray());
        } elseif (is_object($object) && $object instanceof \ArrayObject) {
            return $object->offsetExists($name);
        } elseif (is_object($object)) {
            return property_exists($object, $name);
        } elseif (is_array($object) || $object instanceof \ArrayAccess) {
            return array_key_exists($name, $object);
        } else {
            throw new \InvalidArgumentException('Please implement your own property accessor.');
        }
    }

    /**
     * @param mixed $object
     * @param string $name
     * @return mixed
     */
    public static function getProperty($object, $name)
    {
        return isset($object->$name) || (is_object($object) && property_exists($object, $name))
            ? $object->$name
            : $object[$name];
    }

    /**
     * @param mixed $object
     * @param string $name
     * @param string $value
     */
    public static function setProperty($object, $name, $value)
    {
        if (isset($object->$name) || (is_object($object) && property_exists($object, $name))) {
            $object->$name = $value;
        } else {
            $object[$name] = $value;
        }
    }
}
