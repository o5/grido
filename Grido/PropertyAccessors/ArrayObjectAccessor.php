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
     * @throws PropertyAccessorException
     * @throws \Nette\MemberAccessException
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public static function getProperty($object, $name)
    {
        if (is_array($object)) {
            if (!array_key_exists($name, $object)) {
                throw new PropertyAccessorException("Property with name '$name' does not exists in datasource.");
            }

            return $object[$name];

        } elseif (is_object($object)) {
            if (\Nette\Utils\Strings::contains($name, '.')) {
                $parts = explode('.', $name);
                foreach ($parts as $item) {
                    if (is_object($object)) {
                        $object = $object->$item;
                    }
                }

                return $object;
            }

            return $object->$name;

        } else {
            throw new \InvalidArgumentException('Please implement your own property accessor.');
        }
    }

    /**
     * @param mixed $object
     * @param string $name
     * @param mixed $value
     */
    public static function setProperty($object, $name, $value)
    {
        if (is_array($object)) {
            $object[$name] = $value;
        } elseif (is_object($object)) {
            $object->$name = $value;
        } else {
            throw new \InvalidArgumentException('Please implement your own property accessor.');
        }
    }
}
