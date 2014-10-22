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
 * Number column.
 *
 * @package     Grido
 * @subpackage  Components\Columns
 * @author      Petr Bugyík
 *
 * @property array $numberFormat
 */
class Number extends Editable
{
    /** @var array */
    protected $numberFormat = array(
        self::NUMBER_FORMAT_DECIMALS => 0,
        self::NUMBER_FORMAT_DECIMAL_POINT => '.',
        self::NUMBER_FORMAT_THOUSANDS_SEPARATOR => ','
    );

    /** @const keys of array $numberFormat */
    const NUMBER_FORMAT_DECIMALS = 0;
    const NUMBER_FORMAT_DECIMAL_POINT = 1;
    const NUMBER_FORMAT_THOUSANDS_SEPARATOR = 2;

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
     * Sets number format. Params are same as internal function number_format().
     * @param int $decimals number of decimal points
     * @param string $decPoint separator for the decimal point
     * @param string $thousandsSep thousands separator
     * @return Number
     */
    public function setNumberFormat($decimals = NULL, $decPoint = NULL, $thousandsSep = NULL)
    {
        if ($decimals !== NULL) {
            $this->numberFormat[self::NUMBER_FORMAT_DECIMALS] = (int) $decimals;
        }

        if ($decPoint !== NULL) {
            $this->numberFormat[self::NUMBER_FORMAT_DECIMAL_POINT] = $decPoint;
        }

        if ($thousandsSep !== NULL) {
            $this->numberFormat[self::NUMBER_FORMAT_THOUSANDS_SEPARATOR] = $thousandsSep;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getNumberFormat()
    {
        return $this->numberFormat;
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function formatValue($value)
    {
        $value = parent::formatValue($value);

        $decimals = $this->numberFormat[self::NUMBER_FORMAT_DECIMALS];
        $decPoint = $this->numberFormat[self::NUMBER_FORMAT_DECIMAL_POINT];
        $thousandsSep = $this->numberFormat[self::NUMBER_FORMAT_THOUSANDS_SEPARATOR];

        return is_numeric($value)
            ? number_format($value, $decimals, $decPoint, $thousandsSep)
            : $value;
    }
}
