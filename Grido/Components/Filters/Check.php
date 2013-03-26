<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\Components\Filters;

/**
 * Check box filter.
 *
 * @package     Grido
 * @subpackage  Components\Filters
 * @author      Petr Bugyík
 */
class Check extends Filter
{
    /* representation TRUE in URI */
    const TRUE = '✓';

    /** @var string for ->where('<column> IS NOT NULL) */
    protected $condition = 'IS NOT NULL';

    /** @var string */
    protected $formatValue;

    /**
     * @return \Nette\Forms\Controls\Checkbox
     */
    protected function getFormControl()
    {
        return new \Nette\Forms\Controls\Checkbox($this->label);
    }

    /**
     * @internal
     * @param string $value
     * @return array
     */
    public function makeFilter($value)
    {
        return parent::makeFilter($value == self::TRUE ? TRUE : FALSE);
    }
    
    /**
    * @param string $column
    * @param string $value
    * @return array
    */
    public function _makeFilter($column, $value)
    {
        return array("[$column] " . $this->condition, '');
    }

    /**
     * @internal
     * @param bool $value
     * @return string
     */
    public function changeValue($value)
    {
        return $value === TRUE ? self::TRUE : $value;
    }
}
