<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\Filters;

/**
 * Check box filter.
 *
 * @package     Grido
 * @subpackage  Filters
 * @author      Petr Bugyík
 */
class Check extends Filter
{
    /* representation TRUE in URI */
    const TRUE = '✓';

    /** @var string for ->where('<column> IS NOT NULL, <value>) */
    protected $condition = 'IS NOT NULL';

    /** @var string for ->where('<column> IS NOT NULL) */
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
        return parent::makeFilter($value == self::TRUE ? TRUE : $value);
    }

    /**
     * @internal
     * @param string $value
     * @return string
     */
    public function changeValue($value)
    {
        return $value === TRUE ? self::TRUE : $value;
    }
}
