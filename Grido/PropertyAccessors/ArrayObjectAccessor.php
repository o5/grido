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
     * @param $object
     * @param $name
     * @return mixed
     */
    public static function hasProperty($object, $name)
    {
        return (
            (is_array($object) || $object instanceof \ArrayAccess) && (isset($object[$name]) || array_key_exists($name, $object)))
            || (is_object($object) && (isset($object->$name) || property_exists($object, $name))
            );
    }


    /**
     * @param $object
     * @param $name
     * @return mixed
     */
    public static function getProperty($object, $name)
    {
        return isset($object->$name) || property_exists($object, $name) ? $object->$name : $object[$name];
    }


    /**
     * @param $object
     * @param $name
     * @param $value
     * @return mixed
     */
    public static function setProperty($object, $name, $value)
    {
        if (isset($object->$name) || property_exists($object, $name)) {
            $object->$name = $value;
        } else {
            $object[$name] = $value;
        }
    }
}
