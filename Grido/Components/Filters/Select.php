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
 * Select box filter.
 *
 * @package     Grido
 * @subpackage  Components\Filters
 * @author      Petr Bugyík
 */
class Select extends Filter
{
    /** @var string for ->where('<column> = %s', <value>) */
    protected $condition = '= %s';

    /**
     * @param \Grido\Grid $grid
     * @param string $name
     * @param string $label
     * @param array $items for select
     */
    public function __construct($grid, $name, $label, array $items = NULL)
    {
        parent::__construct($grid, $name, $label);

        if ($items !== NULL) {
            $this->getControl()->setItems($items);
        }
    }

    /**
     * @return \Nette\Forms\Controls\SelectBox
     */
    protected function getFormControl()
    {
        $control = new \Nette\Forms\Controls\SelectBox($this->label);
        $control->controlPrototype->class[] = 'text';
        return $control;
    }
}
