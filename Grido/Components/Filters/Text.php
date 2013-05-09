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

        if ($this->suggestsCallback) {
            $items = callback($this->suggestsCallback)->invokeArgs(array($query, $conditions, $this));
        } else {
            $items = $this->grid->model->suggest(key($this->getColumns()), $conditions);
        }

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
