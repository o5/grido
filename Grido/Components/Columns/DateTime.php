<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\Components\Columns;

/**
 * DateTime column.
 *
 * @package     Grido
 * @subpackage  Components\Columns
 * @author      Josef Kříž <pepakriz@gmail.com>
 */
class DateTime extends Date
{
    /**
     * @param $value
     * @return string
     */
    protected function formatValue($value)
    {
        return $value ? $value->format($this->dateFormat) : NULL;
    }
}
