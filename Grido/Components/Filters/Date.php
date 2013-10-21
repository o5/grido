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
 * Date input filter.
 *
 * @package     Grido
 * @subpackage  Components\Filters
 * @author      Petr Bugyík
 */
class Date extends Text
{
    /** @var string */
    protected $condition = '= ?';

    /** @var string */
    protected $formatValue;

    /**
     * @return \Nette\Forms\Controls\TextInput
     */
    protected function getFormControl()
    {
        $control = parent::getFormControl();
        $control->getControlPrototype()->class[] = 'date';
        $control->getControlPrototype()->attrs['autocomplete'] = 'off';

        return $control;
    }
}
