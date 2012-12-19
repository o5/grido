<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\Columns;

/**
 * Text column.
 *
 * @package     Grido
 * @subpackage  Columns
 * @author      Petr Bugyík
 *
 * @property-write string $dateFormat
 */
class Date extends Text
{
    const FORMAT_TEXT = 'd M Y';
    const FORMAT_DATE = 'd.m.Y';
    const FORMAT_DATETIME = 'd.m.Y H:i:s';

    /** @var string */
    protected $dateFormat = self::FORMAT_DATE;

    /**
     * @param string $format
     * @return \Grido\Columns\Date
     */
    public function setDateFormat($format)
    {
        $this->dateFormat = $format;
        return $this;
    }

    /**
     * @param $value
     * @return string
     */
    protected function formatValue($value)
    {
        return $value ? date($this->dateFormat, strtotime($value)) : NULL;
    }
}
