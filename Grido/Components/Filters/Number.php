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
    /** @var string */
    protected $condition;

    /**
     * @return \Nette\Forms\Controls\TextInput
     */
    protected function getFormControl()
    {
        $control = parent::getFormControl();
        $hint = 'You can use <, <=, >, >=, <>. e.g. ">= %d"';
        $control->controlPrototype->title = sprintf($this->translate($hint), rand(1, 9));
        $control->controlPrototype->class[] = 'number';

        return $control;
    }

    /**
     * @internal - do not call directly.
     * @param string $value
     * @return Condition
     * @throws \Exception
     */
    public function __getCondition($value)
    {
        $condition = parent::__getCondition($value);

        if ($condition === NULL) {
            $condition = Condition::setupEmpty();

            if (preg_match('/(<>|[<|>]=?)?([-0-9,|.]+)/', $value, $matches)) {
                $value = str_replace(',', '.', $matches[2]);
                $operator = $matches[1]
                    ? $matches[1]
                    : '=';

                $condition = Condition::setup($this->getColumn(), $operator . ' ?', $value);
            }
        }

        return $condition;
    }
}
