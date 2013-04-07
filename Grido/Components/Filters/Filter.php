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
 * Data filtering.
 *
 * @package     Grido
 * @subpackage  Components\Filters
 * @author      Petr Bugyík
 *
 * @property-read string $columns
 * @property-read string $wrapperPrototype
 * @property-read \Nette\Forms\Controls\BaseControl $control
 * @property-write mixed $condition
 * @property-write string $formatValue
 * @property-write string $defaultValue
 */
abstract class Filter extends \Grido\Components\Base
{
    const ID = 'filters';

    const TYPE_TEXT = 'Grido\Components\Filters\Text';
    const TYPE_DATE = 'Grido\Components\Filters\Date';
    const TYPE_CHECK = 'Grido\Components\Filters\Check';
    const TYPE_SELECT = 'Grido\Components\Filters\Select';
    const TYPE_NUMBER = 'Grido\Components\Filters\Number';

    const OPERATOR_AND  = 'AND';
    const OPERATOR_OR   = 'OR';

    const VALUE_IDENTIFIER = '%value';

    const CONDITION_CUSTOM = ':condition-custom:';
    const CONDITION_CALLBACK = ':condition-callback:';
    const CONDITION_NOT_APPLY = ':not-apply:';

    const RENDER_INNER = 'inner';
    const RENDER_OUTER = 'outer';

    /** @var mixed */
    protected $optional;

    /** @var array */
    protected $columns = array();

    /** @var mixed for ->where('<column> = %s', <value>)  */
    protected $condition = '= %s';

    /** @var string */
    protected $formatValue;

    /** @var \Nette\Utils\Html */
    protected $wrapperPrototype;

    /** @var \Nette\Forms\Controls\BaseControl */
    protected $control;

    /**
     * @param \Grido\Grid $grid
     * @param string $name
     * @param string $label
     * @param mixed $optional - if TYPE_SELECT then this it items for select
     */
    public function __construct($grid, $name, $label, $optional = NULL)
    {
        $this->addComponentToGrid($grid, $name);

        $this->label = $label;
        $this->type = get_class($this);

        $form = $this->getForm();
        $filters = $form->getComponent(self::ID, FALSE);
        if ($filters === NULL) {
            $filters = $form->addContainer(self::ID);
        }

        $filters->addComponent($this->getFormControl(), $name);
    }

    /**********************************************************************************************/

    /**
     * Map to database column.
     * @param string $column
     * @param string $operator
     * @return Filter
     */
    public function setColumn($column, $operator = self::OPERATOR_AND)
    {
        $this->columns[$column] = $operator;
        return $this;
    }

    /**
     * Sets custom sql condition.
     * @param $condition
     * @param mixed $custom
     * @throws \InvalidArgumentException
     * @return Filter
     */
    public function setCondition($condition, $custom = NULL)
    {
        if (in_array($condition, array(self::CONDITION_CUSTOM, self::CONDITION_CALLBACK))) {
            if (empty($custom)) {
                throw new \InvalidArgumentException('Second param cannot be empty.');
            }
            $this->condition = array($condition => $custom);
        } else {
            $this->condition = $condition;
        }

        return $this;
    }

    /**
     * Sets custom format value.
     * @param string $format for example: "%%value%"
     * @return Filter
     */
    public function setFormatValue($format)
    {
        $this->formatValue = $format;
        return $this;
    }

    /**
     * Sets default value.
     * @param string $value
     * @return Filter
     */
    public function setDefaultValue($value)
    {
        $this->grid->setDefaultFilter(array($this->name => $value));
        return $this;
    }

    /**********************************************************************************************/

    /**
     * @internal
     * @return array
     */
    public function getColumns()
    {
        if (!$this->columns) {
            $this->setColumn($this->name);
        }

        return $this->columns;
    }

    /**
     * @internal
     * @return \Nette\Forms\Controls\BaseControl
     */
    public function getControl()
    {
        if ($this->control === NULL) {
            $this->control = $this->getForm()->getComponent(self::ID)->getComponent($this->name);
        }

        return $this->control;
    }

    /**
     * Returns wrapper prototype (<th> html tag).
     * @return \Nette\Utils\Html
     */
    public function getWrapperPrototype()
    {
        if (!$this->wrapperPrototype) {
            $this->wrapperPrototype = \Nette\Utils\Html::el('th')
                ->setClass(array('grid-filter-' . $this->getName()));
        }

        return $this->wrapperPrototype;
    }

    /**********************************************************************************************/

    /**
     * @internal
     * @param string $value
     * @return array
     */
    public function makeFilter($value)
    {
        if ($this->condition == self::CONDITION_NOT_APPLY) {
            return array();
        }

        $customCallback = is_array($this->condition) && isset($this->condition[self::CONDITION_CALLBACK])
            ? $this->condition[self::CONDITION_CALLBACK]
            : FALSE;

        if ($customCallback) {
            return callback($customCallback)->invokeArgs(array($value));
        }

        $values = array();
        $addOperator = FALSE;
        $condition = array();
        $columns = $this->getColumns();
        $moreColumns = count($columns) > 1;
        $customCondition = is_array($this->condition) && isset($this->condition[self::CONDITION_CUSTOM])
            ? $this->condition[self::CONDITION_CUSTOM]
            : FALSE;

        foreach ($columns as $column => $operator ) {
            if ($addOperator) {
                $condition[] = " $operator ";
            }

            if ($moreColumns) {
                $condition[] = '(';
            }

            if ($customCondition) {
                if (isset($customCondition[$value])) {
                    $condition[] = $customCondition[$value];
                }
            } elseif ($filter = $this->_makeFilter($column, $value)) {
                $condition[] = $filter[0];
                $values[] = $filter[1];
            }

            if ($moreColumns) {
                $condition[] = ')';
            }
            $addOperator = TRUE;
        }

        if ($condition) {
            array_unshift($condition, ' (');
            $condition[] = ' )';
        }

        if (!$customCondition && $condition) {
            $condition = array(implode('', $condition));

            foreach ($values as $val) {
                $condition[] = $val;
            }
        }

        return $condition;
    }

    /**
     * @param string $column
     * @param string $value
     * @return array condition|value
     */
    protected function _makeFilter($column, $value)
    {
        return array("[$column] " . $this->condition, $this->formatValue($value));
    }

    /**
     * Format value for database.
     * @param string $value
     * @return string
     */
    protected function formatValue($value)
    {
        if ($this->formatValue !== NULL) {
            return str_replace(self::VALUE_IDENTIFIER, $value, $this->formatValue);
        } else {
            return $value;
        }
    }

    /**
     * Value representation in URI.
     * @internal
     * @param string $value
     * @return string
     */
    public function changeValue($value)
    {
        return $value;
    }
}
