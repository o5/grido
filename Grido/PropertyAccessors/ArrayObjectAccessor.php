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
     * @return mixed
     */
    public static function getProperty($object, $name)
    {
        if ($object instanceof \Nette\Database\Table\ActiveRow) {
            //https://github.com/nette/nette/pull/1100
            $object = $object->toArray();
        }

        if (is_array($object) && array_key_exists($name, $object)) {
            return $object[$name];
        } elseif (is_object($object) && property_exists($object, $name)) {
            return $object->$name;
        } else {
            throw new PropertyAccessorException("Property with name '$name' does not exists in datasource.");
        }
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
