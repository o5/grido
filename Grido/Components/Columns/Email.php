<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\Components\Columns;

/**
 * Email column.
 *
 * @package     Grido
 * @subpackage  Components\Columns
 * @author      Petr Bugyík
 */
class Email extends Link
{
    protected function formatHref($value)
    {
        return "mailto:" . $value;
    }

    protected function getAnchor($value)
    {
        $anchor = parent::getAnchor($value);
        unset($anchor->attrs['target']);

        return $anchor;
    }
}
