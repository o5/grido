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
    /** @var string */
    protected $condition = 'BETWEEN ? AND ?';

    /**
     * @var string
     */
    protected $dateFormatOutput = 'Y-m-d';

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
        if ($this->where === NULL && is_string($this->condition)) {
            list (, $from, $to) = \Nette\Utils\Strings::match($value, $this->mask);
            $from = \DateTime::createFromFormat($this->dateFormatInput, trim($from));
            $to = \DateTime::createFromFormat($this->dateFormatInput, trim($to));

            $values = $from && $to
                ? array($from->format($this->dateFormatOutput.' 00:00:00'), $to->format($this->dateFormatOutput.' 23:59:59'))
                : NULL;

            return $values
                ? Condition::setup($this->getColumn(), $this->condition, $values)
                : Condition::setupEmpty();
        }

        return parent::__getCondition($value);
    }
}
