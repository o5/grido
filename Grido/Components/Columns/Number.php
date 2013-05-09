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
 * Number column.
 *
 * @package     Grido
 * @subpackage  Components\Columns
 * @author      Petr Bugyík
 */
class Number extends Text
{
    /** @var array */
    protected $numberFormat = array(0, '.', ',');

    /**
     * @param \Grido\Grid $grid
     * @param string $name
     * @param string $label
     * @param int $decimals number of decimal points
     * @param string $decPoint separator for the decimal point
     * @param string $thousandsSep thousands separator
     */
    public function __construct($grid, $name, $label, $decimals = NULL, $decPoint = NULL, $thousandsSep = NULL)
    {
        parent::__construct($grid, $name, $label);

        $this->setNumberFormat($decimals, $decPoint, $thousandsSep);
    }

    /**
     * @param int $decimals number of decimal points
     * @param string $decPoint separator for the decimal point
     * @param string $thousandsSep thousands separator
     * @return Number
     */
    public function setNumberFormat($decimals = NULL, $decPoint = NULL , $thousandsSep = NULL)
    {
        if ($decimals !== NULL) {
            $this->numberFormat[0] = (int) $decimals;
        }

        if ($decPoint !== NULL) {
            $this->numberFormat[1] = $decPoint;
        }

        if ($thousandsSep !== NULL) {
            $this->numberFormat[2] = $thousandsSep;
        }

        return $this;
    }

    /**
    * @param mixed $value
    * @return string
    */
    protected function formatValue($value)
    {
        return is_numeric($value)
            ? number_format($value, $this->numberFormat[0], $this->numberFormat[1], $this->numberFormat[2])
            : $value;
    }
}
