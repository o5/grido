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
 * Number input filter.
 *
 * @package     Grido
 * @subpackage  Components\Filters
 * @author      Petr Bugyík
 */
class Number extends Text
{
    /** @var string for ->where('<column> <> %f', <value>) */
    protected $condition = '/(<>|[<|>]=?)?([0-9,|.]+)/';

    /**
     * @return \Nette\Forms\Controls\TextInput
     */
    protected function getFormControl()
    {
        $control = parent::getFormControl();
        $hint = 'You can use <, <=, >, >=, <>. e.g. ">= %d"';
        $control->controlPrototype->title = $this->translate($hint, rand(1, 9));
        $control->controlPrototype->class[] = 'number';
        return $control;
    }

    /**
     * @param string $column
     * @param string $value
     * @return array condition|value
     */
    protected function _makeFilter($column, $value)
    {
        $condition = NULL;
        if (preg_match($this->condition, $value, $matches)) {
            $operator = $matches[1] ? $matches[1] : '=';
            $condition = array(
                "[$column] $operator %f",
                $matches[2]
            );
        }
        return $condition;
    }
}
