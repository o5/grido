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

use Grido\Exception;

/**
 * Text input filter.
 *
 * @package     Grido
 * @subpackage  Components\Filters
 * @author      Petr Bugyík
 *
 * @property int $suggestionLimit
 * @property-write callback $suggestionCallback
 */
class Text extends Filter
{
    /** @var string */
    protected $condition = 'LIKE ?';

    /** @var string */
    protected $formatValue = '%%value%';

    /** @var bool */
    protected $suggestion = FALSE;

    /** @var mixed */
    protected $suggestionColumn;

    /** @var int */
    protected $suggestionLimit = 10;

    /** @var callback */
    protected $suggestionCallback;

    /**
     * Allows suggestion.
     * @param mixed $column
     * @return Text
     */
    public function setSuggestion($column = NULL)
    {
        $this->suggestion = TRUE;
        $this->suggestionColumn = $column;

        $prototype = $this->getControl()->getControlPrototype();
        $prototype->attrs['autocomplete'] = 'off';
        $prototype->class[] = 'suggest';

        $filter = $this;
        $this->grid->onRender[] = function() use ($prototype, $filter) {
            $replacement = '-query-';
            $prototype->data['grido-suggest-replacement'] = $replacement;
            $prototype->data['grido-suggest-limit'] = $filter->suggestionLimit;
            $prototype->data['grido-suggest-handler'] = $filter->link('suggest!', array(
                'query' => $replacement
            ));
        };

        return $this;
    }

    /**
     * Sets a limit for suggestion select.
     * @param int $limit
     * @return \Grido\Components\Filters\Text
     */
    public function setSuggestionLimit($limit)
    {
        $this->suggestionLimit = (int) $limit;
        return $this;
    }

    /**
     * Sets custom data callback.
     * @param callback $callback
     * @return \Grido\Components\Filters\Text
     */
    public function setSuggestionCallback($callback)
    {
        $this->suggestionCallback = $callback;
        return $this;
    }

    /**********************************************************************************************/

    /**
     * @return int
     */
    public function getSuggestionLimit()
    {
        return $this->suggestionLimit;
    }

    /**
     * @return callback
     */
    public function getSuggestionCallback()
    {
        return $this->suggestionCallback;
    }

    /**
     * @return string
     */
    public function getSuggestionColumn()
    {
        return $this->suggestionColumn;
    }

    /**
     * @param string $query - value from input
     * @internal
     * @throws Exception
     */
    public function handleSuggest($query)
    {
        $this->grid->onRegistered && $this->grid->onRegistered($this->grid);
        $name = $this->getName();

        if (!$this->getPresenter()->isAjax() || !$this->suggestion || $query == '') {
            $this->getPresenter()->terminate();
        }

        $actualFilter = $this->grid->getActualFilter();
        if (isset($actualFilter[$name])) {
            unset($actualFilter[$name]);
        }

        $conditions = $this->grid->__getConditions($actualFilter);

        if ($this->suggestionCallback === NULL) {
            $conditions[] = $this->__getCondition($query);

            $column = $this->suggestionColumn ? $this->suggestionColumn : current($this->getColumn());
            $items = $this->grid->model->suggest($column, $conditions, $this->suggestionLimit);

        } else {
            $items = callback($this->suggestionCallback)->invokeArgs(array($query, $actualFilter, $conditions, $this));
            if (!is_array($items)) {
                throw new Exception('Items must be an array.');
            }
        }

        $this->getPresenter()->sendResponse(new \Nette\Application\Responses\JsonResponse($items));
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
