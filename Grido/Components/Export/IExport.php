<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido;

/**
 * @package     Grido
 * @subpackage  Export
 * @author      Petr Bugyík
 */
interface IExport extends \Nette\Application\IResponse
{
    /**
     * @param string $type
     * @return bool
     */
    function hasType($type);
}
