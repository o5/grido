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
 * Text input filter.
 *
 * @package     Grido
 * @subpackage  Components\Filters
 * @author      Petr Bugyík
 */
class Text extends Filter
{
    /** @var callback */
    public $suggestsCallback;

    /** @var string for ->where('<column> LIKE %s', <value>) */
    protected $condition = 'LIKE %s';

    /** @var string for ->where('<column> LIKE %s', '%'.<value>.'%') */
    protected $formatValue = '%%value%';

    /**
     * Allows suggestion.
     * @param callback $callback
     * @return Text
     */
    public function setSuggestion($callback = NULL)
    {
        $this->suggestsCallback = $callback;

        $prototype = $this->getControl()->controlPrototype;
        $prototype->class[] = 'suggest';
        $prototype->attrs['autocomplete'] = 'off';

        $name = $this->name;
        $this->grid->onRender[] = function(\Grido\Grid $grid) use ($prototype, $name) {
            $prototype->attrs['data-grido-source'] = $grid->link('suggest!', $name);
        };

        return $this;
    }

    /**********************************************************************************************/

    /**
     * @return \Nette\Forms\Controls\TextInput
     */
    protected function getFormControl()
    {
        $control = new \Nette\Forms\Controls\TextInput($this->label);
        $control->controlPrototype->class[] = 'text';
        return $control;
    }
}
