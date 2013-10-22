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

    /** @var string */
    protected $condition = 'LIKE ?';

    /** @var string */
    protected $formatValue = '%%value%';

    /**
     * Allows suggestion.
     * @param mixed $column
     * @return Text
     */
    public function setSuggestion($column = NULL)
    {
        $this->suggestionColumn = $column;

        $prototype = $this->getControl()->getControlPrototype();
        $prototype->attrs['autocomplete'] = 'off';
        $prototype->class[] = 'suggest';

        $filter = $this;
        $this->grid->onRender[] = function(\Grido\Grid $grid) use ($prototype, $filter) {
            $replacement = '-query-';
            $prototype->data['grido-suggest-replacement'] = $replacement;
            $prototype->data['grido-suggest-handler'] = $filter->link('suggest!', array(
                'query' => $replacement)
            );
        };

        return $this;
    }

    /**********************************************************************************************/

    /**
     * @param string $query - value from input
     * @internal
     */
    public function handleSuggest($query)
    {
        if (!$this->getPresenter()->isAjax()) {
            $this->getPresenter()->terminate();
        }

        $actualFilter = $this->grid->getActualFilter();
        if (isset($actualFilter[$this->getName()])) {
            unset($actualFilter[$this->getName()]);
        }
        $conditions = $this->grid->__getConditions($actualFilter);
        $conditions[] = $this->__getCondition($query);

        $column = $this->suggestionColumn ? $this->suggestionColumn : current($this->getColumn());
        $items = $this->grid->model->suggest($column, $conditions);

        print \Nette\Utils\Json::encode($items);
        $this->getPresenter()->terminate();
    }

    /**
     * @return \Nette\Forms\Controls\TextInput
     */
    protected function getFormControl()
    {
        $control = new \Nette\Forms\Controls\TextInput($this->label);
        $control->getControlPrototype()->class[] = 'text';

        return $control;
    }
}
