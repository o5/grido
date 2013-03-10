<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\DataSources;

/**
 * Base of data source.
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Petr Bugyík
 */
abstract class Base extends \Nette\Object
{
    /** @var array of callbacks */
    public $proxy = array();

    /**
     * @param string $method
     * @return mixed
     */
    public function call($method)
    {
        $args = func_get_args();
        unset($args[0]);

        return isset($this->proxy[$method])
            ? callback($this->proxy[$method])->invokeArgs(array($this, $args))
            : call_user_func_array(array($this, $method), $args);
    }
}
