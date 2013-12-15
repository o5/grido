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
 * Date-range input filter.
 *
 * @package     Grido
 * @subpackage  Components\Filters
 * @author      Petr Bugyík
 *
 * @property string $mask
 */
class DateRange extends Date
{
    const DEFAULT_CONDITION = 'BETWEEN ? AND ?';

    /** @var string */
    protected $condition = self::DEFAULT_CONDITION;

    /** @var string */
    protected $formatValue;

    /**
     * @var string
     */
    protected $mask = '/(.*)\s?-\s?(.*)/';

    /**
     * Sets mask by regular expression.
     * @param string $mask
     * @return DateRange
     */
    public function setMask($mask)
    {
        $this->mask = $mask;
        return $this;
    }

    /**
     * @return string
     */
    public function getMask()
    {
        return $this->mask;
    }

    /**
     * @return \Nette\Forms\Controls\TextInput
     */
    protected function getFormControl()
    {
        $control = parent::getFormControl();

        $prototype = $control->getControlPrototype();
        array_pop($prototype->class); //remove "date" class
        $prototype->class[] = 'daterange';

        return $control;
    }

    /**
     * @param string $value
     * @return Condition
     * @throws \Exception
     * @internal
     */
    public function __getCondition($value)
    {
        if ($this->where === NULL && $this->condition == self::DEFAULT_CONDITION) {
            list (, $from, $to) = \Nette\Utils\Strings::match($value, $this->mask);
            $from = \DateTime::createFromFormat($this->dateFormatInput, trim($from));
            $to = \DateTime::createFromFormat($this->dateFormatInput, trim($to));

            $values = $from && $to
                ? array($from->format($this->dateFormatOutput), $to->format($this->dateFormatOutput))
                : NULL;

            return $values
                ? Condition::setup($this->getColumn(), $this->condition, $values)
                : Condition::setupEmpty();
        }

        return parent::__getCondition($value);
    }
}
