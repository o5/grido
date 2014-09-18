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
 * Symfony property accessor.
 *
 * @package     Grido
 * @subpackage  PropertyAccessors
 * @author      Josef Kříž <pepakriz@gmail.com>
 * @link        http://symfony.com/doc/current/components/property_access/introduction.html
 */
class SymfonyPropertyAccessor implements IPropertyAccessor
{
    /** @var \Symfony\Component\PropertyAccess\PropertyAccessor */
    private $propertyAccessor;

    /**
     * @param mixed $object
     * @param string $name
     * @return mixed
     * @throws PropertyAccessorException
     */
    public function getProperty($object, $name)
    {
        try {
            return $this->getPropertyAccessor()->getValue($object, $name);
        } catch (\Exception $e) {
            throw new PropertyAccessorException("Property with name '$name' does not exists in datasource.", 0, $e);
        }
    }

    /**
     * @param mixed $object
     * @param string $name
     * @param mixed $value
     * @throws PropertyAccessorException
     */
    public function setProperty($object, $name, $value)
    {
        try {
            $this->getPropertyAccessor()->setValue($object, $name, $value);
        } catch (\Exception $e) {
            throw new PropertyAccessorException("Property with name '$name' does not exists in datasource.", 0, $e);
        }
    }

    /**
     * @return \Symfony\Component\PropertyAccess\PropertyAccessor
     */
    private function getPropertyAccessor()
    {
        if ($this->propertyAccessor === NULL) {
            $this->propertyAccessor = new \Symfony\Component\PropertyAccess\PropertyAccessor(TRUE, TRUE);
        }

        return $this->propertyAccessor;
    }
}
