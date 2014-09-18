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
 * @package     Grido
 * @subpackage  PropertyAccessors
 * @author      Josef Kříž <pepakriz@gmail.com>
 */
interface IPropertyAccessor
{
    /**
     * @param mixed $object
     * @param string $name
     * @return mixed
     */
    public function getProperty($object, $name);

    /**
     * @param mixed $object
     * @param string $name
     * @param string $value
     * @return void
     */
    public function setProperty($object, $name, $value);
}
