<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
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

    /** @var string */
    protected $condition = 'IS NOT NULL';

    /**
     * @return \Nette\Forms\Controls\Checkbox
     */
    protected function getFormControl()
    {
        $control = new \Nette\Forms\Controls\Checkbox($this->label);
        $control->getControlPrototype()->class[] = 'checkbox';
        return $control;
    }

    /**
     * @param string $value
     * @return Condition|bool
     * @internal
     */
    public function __getCondition($value)
    {
        $value = $value == self::TRUE
            ? TRUE
            : FALSE;

        return parent::__getCondition($value);
    }

    /**
     * @param bool $value
     * @return NULL
     * @internal
     */
    public function formatValue($value)
    {
        return NULL;
    }

    /**
     * @param bool $value
     * @return string
     * @internal
     */
    public function changeValue($value)
    {
        return (bool) $value === TRUE
            ? self::TRUE
            : $value;
    }
}
