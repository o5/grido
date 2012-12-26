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
 * Select box filter.
 *
 * @package     Grido
 * @subpackage  Filters
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
    public function __construct($grid, $name, $label, $items = array())
    {
        parent::__construct($grid, $name, $label);
        $this->getControl()->setItems((array) $items);
    }

    /**
     * Removes items for empty results.
     * @experimental
     * @param array $items
     */
    public function removeEmptyItems(array $items = array('' => ''))
    {
        $filter = $this;
        $this->grid->onFetchData[] = function(\Grido\Grid $grid) use ($items, $filter)
        {
            if (!empty($grid->filter[$filter->name])) {
                return;
            }

            $column = key($filter->getColumns());
            foreach ($grid->data as $item) {
                $items[$item->$column] = $item->$column;
            }

            $filter->getControl()->setItems($items);
        };
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
