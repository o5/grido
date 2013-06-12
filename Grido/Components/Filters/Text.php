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
    /** @var mixed */
    protected $suggestionColumn;

    /** @var string for ->where('<column> LIKE %s', <value>) */
    protected $condition = 'LIKE %s';

    /** @var string for ->where('<column> LIKE %s', '%'.<value>.'%') */
    protected $formatValue = '%%value%';

    /**
     * Allows suggestion.
     * @param mixed $column
     * @return Text
     */
    public function setSuggestion($column = NULL)
    {
        $this->suggestionColumn = $column;

        $prototype = $this->getControl()->controlPrototype;
        $prototype->attrs['autocomplete'] = 'off';
        $prototype->class[] = 'suggest';

        $filter = $this;
        $this->grid->onRender[] = function(\Grido\Grid $grid) use ($prototype, $filter) {
            $replacement = '-query-';
            $prototype->attrs['data-grido-suggest-replacement'] = $replacement;
            $prototype->attrs['data-grido-suggest-handler'] = $filter->link('suggest!', array(
                'query' => $replacement)
            );
        };

        return $this;
    }

    /**********************************************************************************************/

    /**
     * @internal
     * @param string $query - value from input
     * @throws \InvalidArgumentException
     */
    public function handleSuggest($query)
    {
        if (!$this->grid->presenter->isAjax()) {
            $this->presenter->terminate();
        }

        $actualFilter = $this->grid->getActualFilter();
        if (isset($actualFilter[$this->name])) {
            unset($actualFilter[$this->name]);
        }
        $conditions = $this->grid->_applyFiltering($actualFilter);
        $conditions[] = $this->makeFilter($query);

        $column = $this->suggestionColumn ? $this->suggestionColumn : key($this->getColumns());
        $items = $this->grid->model->suggest($column, $conditions);

        print \Nette\Utils\Json::encode($items);
        $this->grid->presenter->terminate();
    }

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
