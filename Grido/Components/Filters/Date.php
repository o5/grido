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
 * Date input filter.
 *
 * @package     Grido
 * @subpackage  Components\Filters
 * @author      Petr Bugyík
 */
class Date extends Text
{
    /** @var string for ->where('<column> LIKE %s', <value>) */
    protected $condition = 'LIKE %s';

    /** @var string for ->where('<column> LIKE %s', '%'.<value>.'%') */
    protected $formatValue = '%%value%';

    /**
     * @return \Nette\Forms\Controls\TextInput
     */
    protected function getFormControl()
    {
        $control = parent::getFormControl();
        $control->controlPrototype->class[] = 'date';
        $control->controlPrototype->attrs['autocomplete'] = 'off';
        return $control;
    }
}
