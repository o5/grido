<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido;

use Grido\Components\Columns\Column,
    Grido\Components\Filters\Filter,
    Grido\Components\Actions\Action,
    Grido\Components\Operation,
    Grido\Components\Paginator,
    Grido\Components\Export;

/**
 * Grido - DataGrid for Nette Framework.
 *
 * @package     Grido
 * @author      Petr Bugyík
 *
 * @property-read int $count
 * @property-read mixed $data
 * @property-read \Nette\Utils\Html $tablePrototype
 * @property-write string $templateFile
 * @property bool $rememberState
 * @property array $defaultPerPage
 * @property array $defaultFilter
 * @property array $defaultSort
 * @property array $perPageList
 * @property \Nette\Localization\ITranslator $translator
 * @property Paginator $paginator
 * @property string $primaryKey
 * @property string $filterRenderType
 * @property DataSources\IDataSource $model
 * @property PropertyAccessors\IPropertyAccessor $propertyAccessor
 * @property callback $rowCallback
 */
class Grid extends \Nette\Application\UI\Control
{
    /***** DEFAULTS ****/
    const BUTTONS = 'buttons';

    /** @var int @persistent */
    public $page = 1;

    /** @var int @persistent */
    public $perPage;

    /** @var array @persistent */
    public $sort = array();

    /** @var array @persistent */
    public $filter = array();

    /** @var array event on render */
    public $onRender;

    /** @var array event for modifying data */
    public $onFetchData;

    /** @var callback returns tr html element; function($row, Html $tr) */
    protected $rowCallback;

    /** @var \Nette\Utils\Html */
    protected $tablePrototype;

    /** @var bool  */
    protected $rememberState = FALSE;

    /** @var string */
    protected $primaryKey = 'id';

    /** @var string */
    protected $filterRenderType;

    /** @var array */
    protected $perPageList = array(10, 20, 30, 50, 100);

    /** @var int */
    protected $defaultPerPage = 20;

    /** @var array */
    protected $defaultFilter = array();

    /** @var array */
    protected $defaultSort = array();

    /** @var DataSources\IDataSource */
    protected $model;

    /** @var int total count of items */
    protected $count;

    /** @var mixed */
    protected $data;

    /** @var Paginator */
    protected $paginator;

    /** @var \Nette\Localization\ITranslator */
    protected $translator;

    /** @var PropertyAccessors\IPropertyAccessor */
    protected $propertyAccessor;

    /** @var bool cache */
    protected $hasColumns, $hasFilters, $hasActions, $hasOperation, $hasExport;

    /**
     * Sets a model that implements the interface Grido\DataSources\IDataSource or data-source object.
     * @param mixed $model
     * @param bool $forceWrapper
     * @throws \InvalidArgumentException
     * @return Grid
     */
    public function setModel($model, $forceWrapper = FALSE)
    {
        $this->model = $model instanceof DataSources\IDataSource && $forceWrapper === FALSE
            ? $model
            : new DataSources\Model($model);

        return $this;
    }

    /**
     * Sets a property accesor that implements the interface Grido\PropertyAccessors\IPropertyAccessor.
     * @param PropertyAccessors\IPropertyAccessor $propertyAccessor
     * @return Grid
     */
    public function setPropertyAccessor(PropertyAccessors\IPropertyAccessor $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
        return $this;
    }

    /**
     * Sets the default number of items per page.
     * @param int $perPage
     * @return Grid
     */
    public function setDefaultPerPage($perPage)
    {
        $perPage = (int) $perPage;
        $this->defaultPerPage = $perPage;

        if (!in_array($perPage, $this->perPageList)) {
            $this->perPageList[] = $perPage;
            sort($this->perPageList);
        }

        return $this;
    }

    /**
     * Sets default filtering.
     * @param array $filter
     * @return Grid
     */
    public function setDefaultFilter(array $filter)
    {
        $this->defaultFilter = $filter;
        return $this;
    }

    /**
     * Sets default sorting.
     * @param array $sort
     * @return Grid
     */
    public function setDefaultSort(array $sort)
    {
        static $replace = array('asc' => Column::ORDER_ASC, 'desc' => Column::ORDER_DESC);

        foreach ($sort as $column => $dir) {
            $dir = strtr(strtolower($dir), $replace);
            if (!in_array($dir, $replace)) {
                throw new \InvalidArgumentException("Dir '$dir' for column '$column' is not allowed.");
            }

            $this->defaultSort[$column] = $dir;
        }

        return $this;
    }

    /**
     * Sets items to per-page select.
     * @param array $perPageList
     * @return Grid
     */
    public function setPerPageList(array $perPageList)
    {
        $this->perPageList = $perPageList;

        if ($this->hasFilters(FALSE) || $this->hasOperation(FALSE)) {
            $this['form']['count']->setItems($this->getItemsForCountSelect());
        }

        return $this;
    }

    /**
     * Sets translator.
     * @param \Nette\Localization\ITranslator $translator
     * @return Grid
     */
    public function setTranslator(\Nette\Localization\ITranslator $translator)
    {
        $this->translator = $translator;
        return $this;
    }

    /**
     * Sets type of filter rendering.
     * Defaults inner (Filter::RENDER_INNER) if column does not exist then outer filter (Filter::RENDER_OUTER).
     * @param string $type
     * @throws \InvalidArgumentException
     * @return Grid
     */
    public function setFilterRenderType($type)
    {
        $type = strtolower($type);
        if (!in_array($type, array(Filter::RENDER_INNER, Filter::RENDER_OUTER))) {
            throw new \InvalidArgumentException('Type must be Filter::RENDER_INNER or Filter::RENDER_OUTER.');
        }

        $this->filterRenderType = $type;
        return $this;
    }

    /**
     * Sets custom paginator.
     * @param Paginator $paginator
     * @return Grid
     */
    public function setPaginator(Paginator $paginator)
    {
        $this->paginator = $paginator;
        return $this;
    }

    /**
     * Sets grid primary key.
     * Defaults is "id".
     * @param string $key
     */
    public function setPrimaryKey($key)
    {
        $this->primaryKey = $key;
        return $this;
    }

    /**
     * Sets file name of custom template.
     * @param string $file
     * @return Grid
     */
    public function setTemplateFile($file)
    {
        $this->getTemplate()->setFile($file);
        return $this;
    }

    /**
     * Sets saving state to session.
     * @param bool $states
     * @return Grid
     */
    public function setRememberState($state = TRUE)
    {
        $this->rememberState = (bool) $state;
        return $this;
    }

    /**
     * Sets callback for customizing tr html object.
     * Callback returns tr html element; function($row, Html $tr).
     * @param $callback
     * @return Grid
     */
    public function setRowCallback($callback)
    {
        $this->rowCallback = $callback;
        return $this;
    }

    /**
     * Sets client-side options.
     * @param array $options
     * @return Grid
     */
    public function setClientSideOptions(array $options)
    {
        $this->getTablePrototype()->data['grido-options'] = json_encode($options);
        return $this;
    }

    /**********************************************************************************************/

    /**
     * Returns total count of data.
     * @return int
     */
    public function getCount()
    {
        if ($this->count === NULL) {
            $this->count = $this->model->getCount();
        }

        return $this->count;
    }

    /**
     * Returns default per page.
     * @return int
     */
    public function getDefaultPerPage()
    {
        if (!in_array($this->defaultPerPage, $this->perPageList)) {
            $this->defaultPerPage = $this->perPageList[0];
        }

        return $this->defaultPerPage;
    }

    /**
     * Returns default filter.
     * @return array
     */
    public function getDefaultFilter()
    {
        return $this->defaultFilter;
    }

    /**
     * Returns default sort.
     * @return array
     */
    public function getDefaultSort()
    {
        return $this->defaultSort;
    }

    /**
     * Returns list of possible items per page.
     * @return array
     */
    public function getPerPageList()
    {
        return $this->perPageList;
    }

    /**
     * Returns primary key.
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Returns remember state.
     * @return bool
     */
    public function getRememberState()
    {
        return $this->rememberState;
    }

    /**
     * Returns row callback.
     * @return callback
     */
    public function getRowCallback()
    {
        return $this->rowCallback;
    }

    /**
     * Returns items per page.
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage === NULL
            ? $this->getDefaultPerPage()
            : $this->perPage;
    }

    /**
     * Returns column component.
     * @param string $name
     * @param bool $need
     * @return Column
     */
    public function getColumn($name, $need = TRUE)
    {
        return $this->hasColumns()
            ? $this->getComponent(Column::ID)->getComponent($name, $need)
            : NULL;
    }

    /**
     * Returns filter component.
     * @param string $name
     * @param bool $need
     * @return Filter
     */
    public function getFilter($name, $need = TRUE)
    {
        return $this->hasFilters()
            ? $this->getComponent(Filter::ID)->getComponent($name, $need)
            : NULL;
    }

    /**
     * Returns action component.
     * @param string $name
     * @param bool $need
     * @return Action
     */
    public function getAction($name, $need = TRUE)
    {
        return $this->hasActions()
            ? $this->getComponent(Action::ID)->getComponent($name, $need)
            : NULL;
    }

    /**
     * Returns operation component.
     * @param bool $need
     * @return Operation
     */
    public function getOperation($need = TRUE)
    {
        return $this->getComponent(Operation::ID, $need);
    }

    /** @deprecated */
    public function getOperations($need = TRUE)
    {
        trigger_error(__METHOD__ . '() is deprecated; use getOperation() instead.', E_USER_DEPRECATED);
        return $this->getOperation($need);
    }

    /**
     * Returns export component.
     * @param bool $need
     * @return Export
     */
    public function getExport($need = TRUE)
    {
        return $this->getComponent(Export::ID, $need);
    }

    /**
     * Returns actual filter values.
     * @param string $key
     * @return mixed
     */
    public function getActualFilter($key = NULL)
    {
        $filter = $this->filter ? $this->filter : $this->defaultFilter;
        return $key && isset($filter[$key]) ? $filter[$key] : $filter;
    }

    /**
     * Returns fetched data.
     * @param bool $applyPaging
     * @param bool $useCache
     * @throws \Exception
     * @return array
     */
    public function getData($applyPaging = TRUE, $useCache = TRUE)
    {
        if ($this->model === NULL) {
            throw new \Exception('Model cannot be empty, please use method $grid->setModel().');
        }

        $data = $this->data;
        if ($data === NULL || $useCache === FALSE) {
            $this->applyFiltering();
            $this->applySorting();

            if ($applyPaging) {
                $this->applyPaging();
            }

            $data = $this->model->getData();

            if ($useCache === TRUE) {
                $this->data = $data;
            }

            if ($applyPaging && $data && !in_array($this->page, range(1, $this->getPaginator()->pageCount))) {
                trigger_error("Page is out of range.", E_USER_NOTICE);
                $this->page = 1;
            }

            if ($this->onFetchData) {
                $this->onFetchData($this);
            }
        }

        return $data;
    }

    /**
     * Returns translator.
     * @return Translations\FileTranslator
     */
    public function getTranslator()
    {
        if ($this->translator === NULL) {
            $this->setTranslator(new Translations\FileTranslator);
        }

        return $this->translator;
    }

    /**
     * Returns remember session for set expiration, etc.
     * @return \Nette\Http\Session
     */
    public function getRememberSession()
    {
        $presenter = $this->getPresenter();
        return $presenter->getSession($presenter->getName() . '\\' . ucfirst($this->getName()));
    }

    /**
     * Returns table html element of grid.
     * @return \Nette\Utils\Html
     */
    public function getTablePrototype()
    {
        if ($this->tablePrototype === NULL) {
            $this->tablePrototype = \Nette\Utils\Html::el('table')
                ->id($this->getName())
                ->class('table table-striped table-hover');
        }

        return $this->tablePrototype;
    }

    /**
     * @return string
     * @internal
     */
    public function getFilterRenderType()
    {
        if ($this->filterRenderType !== NULL) {
            return $this->filterRenderType;
        }

        $this->filterRenderType = Filter::RENDER_OUTER;
        if ($this->hasColumns() && $this->hasFilters() && $this->hasActions()) {
            $this->filterRenderType = Filter::RENDER_INNER;

            $filters = $this[Filter::ID]->getComponents();
            foreach ($filters as $filter) {
                if (!$this[Column::ID]->getComponent($filter->name, FALSE)) {
                    $this->filterRenderType = Filter::RENDER_OUTER;
                    break;
                }
            }
        }

        return $this->filterRenderType;
    }

    /**
     * @return DataSources\IDataSource
     * @internal
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return PropertyAccessors\IPropertyAccessor
     * @internal
     */
    public function getPropertyAccessor()
    {
        if ($this->propertyAccessor === NULL) {
            $this->propertyAccessor = new PropertyAccessors\ArrayObjectAccessor;
        }

        return $this->propertyAccessor;
    }

    /**
     * @return Paginator
     * @internal
     */
    public function getPaginator()
    {
        if ($this->paginator === NULL) {
            $this->paginator = new Paginator;
            $this->paginator->setItemsPerPage($this->getPerPage())
                            ->setGrid($this);
        }

        return $this->paginator;
    }

    /**
     * @param mixed $row item from db
     * @return \Nette\Utils\Html
     * @internal
     */
    public function getRowPrototype($row)
    {
        $tr = \Nette\Utils\Html::el('tr');
        if ($this->rowCallback) {
            $tr = callback($this->rowCallback)->invokeArgs(array($row, $tr));
        }

        return $tr;
    }

    /**********************************************************************************************/

     /**
      * Loads state informations.
      * @param array $params
      * @internal
      */
    public function loadState(array $params)
    {
        //loads state from session
        $session = $this->getRememberSession();
        if ($this->getPresenter()->isSignalReceiver($this)) {
            $session->remove();
        } elseif (!$params && $session->params) {
            $params = (array) $session->params;
        }

        parent::loadState($params);
    }

    /**
     * Ajax method.
     * @internal
     */
    public function handleRefresh()
    {
        $this->reload();
    }

    /**
     * @param int $page
     * @internal
     */
    public function handlePage($page)
    {
        $this->reload();
    }

    /**
     * @param array $sort
     * @internal
     */
    public function handleSort(array $sort)
    {
        $this->page = 1;
        $this->reload();
    }

    /**
     * @param \Nette\Forms\Controls\SubmitButton $button
     * @internal
     */
    public function handleFilter(\Nette\Forms\Controls\SubmitButton $button)
    {
        $values = $button->form->values[Filter::ID];
        foreach ($values as $name => $value) {
            if ($value != '' || isset($this->defaultFilter[$name])) {
                $this->filter[$name] = $this->getFilter($name)->changeValue($value);
            } elseif (isset($this->filter[$name])) {
                unset($this->filter[$name]);
            }
        }

        $this->page = 1;
        $this->reload();
    }

    /**
     * @param \Nette\Forms\Controls\SubmitButton $button
     * @internal
     */
    public function handleReset(\Nette\Forms\Controls\SubmitButton $button)
    {
        $this->sort = array();
        $this->filter = array();
        $this->perPage = NULL;
        $this->getRememberSession()->remove();
        $button->form->setValues(array(Filter::ID => $this->defaultFilter), TRUE);

        $this->page = 1;
        $this->reload();
    }

    /**
     * @param \Nette\Forms\Controls\SubmitButton $button
     * @internal
     */
    public function handlePerPage(\Nette\Forms\Controls\SubmitButton $button)
    {
        $perPage = (int) $button->form['count']->value;
        $this->perPage = $perPage == $this->defaultPerPage
            ? NULL
            : $perPage;

        $this->page = 1;
        $this->reload();
    }

    /**
     * Refresh wrapper.
     * @return void
     * @internal
     */
    public function reload()
    {
        if ($this->presenter->isAjax()) {
            $this->invalidateControl();
        } else {
            $this->redirect('this');
        }
    }

    /**********************************************************************************************/

    /**
     * @param bool $useCache
     * @return bool
     * @internal
     */
    public function hasColumns($useCache = TRUE)
    {
        $hasColumns = $this->hasColumns;

        if ($hasColumns === NULL || $useCache === FALSE) {
            $container = $this->getComponent(Column::ID, FALSE);
            $hasColumns = $container && count($container->getComponents()) > 0;
            $this->hasColumns = $useCache ? $hasColumns : NULL;
        }

        return $hasColumns;
    }

    /**
     * @param bool $useCache
     * @return bool
     * @internal
     */
    public function hasFilters($useCache = TRUE)
    {
        $hasFilters = $this->hasFilters;

        if ($hasFilters === NULL || $useCache === FALSE) {
            $container = $this->getComponent(Filter::ID, FALSE);
            $hasFilters = $container && count($container->getComponents()) > 0;
            $this->hasFilters = $useCache ? $hasFilters : NULL;
        }

        return $hasFilters;
    }

    /**
     * @param bool $useCache
     * @return bool
     * @internal
     */
    public function hasActions($useCache = TRUE)
    {
        $hasActions = $this->hasActions;

        if ($hasActions === NULL || $useCache === FALSE) {
            $container = $this->getComponent(Action::ID, FALSE);
            $hasActions= $container && count($container->getComponents()) > 0;
            $this->hasActions = $useCache ? $hasActions : NULL;
        }

        return $hasActions;
    }

    /**
     * @param bool $useCache
     * @return bool
     * @internal
     */
    public function hasOperation($useCache = TRUE)
    {
        $hasOperation = $this->hasOperation;
        if ($hasOperation === NULL || $useCache === FALSE) {
            $hasOperation = (bool) $this->getComponent(Operation::ID, FALSE);
            $this->hasOperation = $useCache ? $hasOperation : NULL;
        }

        return $hasOperation;
    }

    /** @deprecated */
    public function hasOperations($useCache = TRUE)
    {
        trigger_error(__METHOD__ . '() is deprecated; use hasOperation() instead.', E_USER_DEPRECATED);
        return $this->hasOperation($useCache);
    }

    /**
     * @param bool $useCache
     * @return bool
     * @internal
     */
    public function hasExport($useCache = TRUE)
    {
        $hasExport = $this->hasExport;

        if ($hasExport === NULL || $useCache === FALSE) {
            $hasExport = (bool) $this->getComponent(Export::ID, FALSE);
            $this->hasExport = $useCache ? $hasExport : NULL;
        }

        return $hasExport;
    }

    /**********************************************************************************************/

    /**
     * @param string $class
     * @return \Nette\Templating\FileTemplate
     * @internal
     */
    public function createTemplate($class = NULL)
    {
        $template = parent::createTemplate($class);
        $template->setFile(__DIR__ . '/Grid.latte');
        $template->registerHelper('translate', callback($this->getTranslator(), 'translate'));

        return $template;
    }

    /**
     * @internal
     */
    public function render()
    {
        if (!$this->hasColumns()) {
            throw new \Exception('Grid must have defined a column, please use method $grid->addColumn*().');
        }

        $this->saveRememberState();
        $data = $this->getData();

        $this->template->paginator = $this->paginator;
        $this->template->data = $data;

        if ($this->onRender) {
            $this->onRender($this);
        }

        $this->template->render();
    }

    protected function saveRememberState()
    {
        if ($this->rememberState) {
            $session = $this->getRememberSession();
            $session->params = $this->params;
        }
    }

    protected function applyFiltering()
    {
        $conditions = $this->__getConditions($this->getActualFilter());
        $this->model->filter($conditions);
    }

    /**
     * @param array $filter
     * @return array
     * @internal
     */
    public function __getConditions(array $filter)
    {
        $conditions = array();
        if ($filter) {
            $this['form']->setDefaults(array(Filter::ID => $filter));

            foreach ($filter as $column => $value) {
                if ($component = $this->getFilter($column, FALSE)) {
                    if ($condition = $component->__getCondition($value)) {
                        $conditions[] = $condition;
                    }
                } else {
                    trigger_error("Filter with name '$column' does not exist.", E_USER_NOTICE);
                }
            }
        }

        return $conditions;
    }

    protected function applySorting()
    {
        $sort = array();
        $this->sort = $this->sort ? $this->sort : $this->defaultSort;

        foreach ($this->sort as $column => $dir) {
            $component = $this->getColumn($column, FALSE);
            if (!$component) {
                trigger_error("Column with name '$column' does not exist.", E_USER_NOTICE);
                break;
            } elseif (!$component->isSortable()) {
                if (isset($this->defaultSort[$column])) {
                    $component->setSortable();
                } else {
                    trigger_error("Column with name '$column' is not sortable.", E_USER_NOTICE);
                    break;
                }
            } elseif (!in_array($dir, array(Column::ORDER_ASC, Column::ORDER_DESC))) {
                if ($dir == '' && isset($this->defaultSort[$column])) {
                    unset($this->sort[$column]);
                    break;
                }

                trigger_error("Dir '$dir' is not allowed.", E_USER_NOTICE);
                break;
            }

            $sort[$component->column] = $dir == Column::ORDER_ASC ? 'ASC' : 'DESC';
        }

        if ($sort) {
            $this->model->sort($sort);
        }
    }

    protected function applyPaging()
    {
        $paginator = $this->getPaginator()
            ->setItemCount($this->getCount())
            ->setPage($this->page);

        $perPage = $this->getPerPage();
        if ($perPage !== NULL && !in_array($perPage, $this->perPageList)) {
            trigger_error("The number '$perPage' of items per page is out of range.", E_USER_NOTICE);
            $perPage = $this->defaultPerPage;
        }

        $this['form']['count']->setValue($perPage);
        $this->model->limit($paginator->getOffset(), $paginator->getLength());
    }

    protected function createComponentForm($name)
    {
        $form = new \Nette\Application\UI\Form($this, $name);
        $form->setTranslator($this->getTranslator());
        $form->setMethod($form::GET);

        $buttons = $form->addContainer(self::BUTTONS);
        $buttons->addSubmit('search', 'Search')
            ->onClick[] = $this->handleFilter;
        $buttons->addSubmit('reset', 'Reset')
            ->onClick[] = $this->handleReset;
        $buttons->addSubmit('perPage', 'Items per page')
            ->onClick[] = $this->handlePerPage;

        $form->addSelect('count', 'Count', $this->getItemsForCountSelect())
            ->controlPrototype->attrs['title'] = $this->getTranslator()->translate('Items per page');
    }

    /**
     * @return array
     */
    protected function getItemsForCountSelect()
    {
        return array_combine($this->perPageList, $this->perPageList);
    }

    /********************************* Components *************************************************/

    /**
     * @param string $name
     * @param string $label
     * @return Components\Columns\Text
     */
    public function addColumnText($name, $label)
    {
        return new Components\Columns\Text($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @return Components\Columns\Mail
     */
    public function addColumnMail($name, $label)
    {
        return new Components\Columns\Mail($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @return Components\Columns\Href
     */
    public function addColumnHref($name, $label)
    {
        return new Components\Columns\Href($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $dateFormat
     * @return Components\Columns\Date
     */
    public function addColumnDate($name, $label, $dateFormat = NULL)
    {
        return new Components\Columns\Date($this, $name, $label, $dateFormat);
    }

    /**
     * @param string $name
     * @param string $label
     * @param int $decimals number of decimal points
     * @param string $decPoint separator for the decimal point
     * @param string $thousandsSep thousands separator
     * @return Components\Columns\Number
     */
    public function addColumnNumber($name, $label, $decimals = NULL, $decPoint = NULL, $thousandsSep = NULL)
    {
        return new Components\Columns\Number($this, $name, $label, $decimals, $decPoint, $thousandsSep);
    }

    /** @deprecated */
    public function addColumn($name, $label, $type = Column::TYPE_TEXT)
    {
        trigger_error(__METHOD__ . '() is deprecated; just create instance of your own type instead.', E_USER_DEPRECATED);

        $column = new $type($this, $name, $label);
        if (!$column instanceof Column) {
            throw new \InvalidArgumentException('Column must be inherited from Components\Columns\Column.');
        }

        return $column;
    }

    /**********************************************************************************************/

    /**
     * @param string $name
     * @param string $label
     * @return Components\Filters\Text
     */
    public function addFilterText($name, $label)
    {
        return new Components\Filters\Text($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @return Components\Filters\Date
     */
    public function addFilterDate($name, $label)
    {
        return new Components\Filters\Date($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @return Components\Filters\Check
     */
    public function addFilterCheck($name, $label)
    {
        return new Components\Filters\Check($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $items
     * @return Components\Filters\Select
     */
    public function addFilterSelect($name, $label, array $items = NULL)
    {
        return new Components\Filters\Select($this, $name, $label, $items);
    }

    /**
     * @param string $name
     * @param string $label
     * @return Components\Filters\Number
     */
    public function addFilterNumber($name, $label)
    {
        return new Components\Filters\Number($this, $name, $label);
    }

    /**
     * @param string $name
     * @param \Nette\Forms\IControl $formControl
     * @return Components\Filters\Custom
     */
    public function addFilterCustom($name, \Nette\Forms\IControl $formControl)
    {
        return new Components\Filters\Custom($this, $name, NULL, $formControl);
    }

    /** @deprecated */
    public function addFilter($name, $label = NULL, $type = Filter::TYPE_TEXT, $optional = NULL)
    {
        trigger_error(__METHOD__ . '() is deprecated; just create instance of your own type instead.', E_USER_DEPRECATED);

        $filter = new $type($this, $name, $label, $optional);
        if (!$filter instanceof Filter) {
            throw new \InvalidArgumentException('Filter must be inherited from \Grido\Components\Filters\Filter.');
        }

        return $filter;
    }

    /**********************************************************************************************/

    /**
     * @param string $name
     * @param string $label
     * @param string $destination
     * @param array $args
     * @return Components\Actions\Href
     */
    public function addActionHref($name, $label, $destination = NULL, array $args = NULL)
    {
        return new Components\Actions\Href($this, $name, $label, $destination, $args);
    }

    /**
     * @param string $name
     * @param string $label
     * @param callback $onClick
     * @return Components\Actions\Event
     */
    public function addActionEvent($name, $label, $onClick = NULL)
    {
        return new Components\Actions\Event($this, $name, $label, $onClick);
    }

    /** @deprecated */
    public function addAction($name, $label, $type = Action::TYPE_HREF, $destination = NULL, array $args = NULL)
    {
        trigger_error(__METHOD__ . '() is deprecated; just create instance of your own type instead.', E_USER_DEPRECATED);

        $action = new $type($this, $name, $label, $destination, $args);
        if (!$action instanceof Action) {
            throw new \InvalidArgumentException('Action must be inherited from \Grido\Components\Actions\Action.');
        }

        return $action;
    }

    /**********************************************************************************************/

    /**
     * @param array $operation
     * @param callback $onSubmit - callback after operation submit
     * @param string $type - operation class - @deprecated
     * @return Operation
     */
    public function setOperation(array $operation, $onSubmit, $type = '\Grido\Components\Operation')
    {
        if ($type !== '\Grido\Components\Operation') {
            trigger_error('Parameter $type is deprecated; just create instance of your own type.', E_USER_DEPRECATED);
        }

        $operation = new $type($this, $operation, $onSubmit);
        if (!$operation instanceof Components\Operation) {
            throw new \InvalidArgumentException('Operation must be inherited from \Grido\Components\Operation.');
        }

        return $operation;
    }

    /** @deprecated */
    public function setOperations(array $operations, $onSubmit, $type = '\Grido\Components\Operation')
    {
        trigger_error(__METHOD__ . '() is deprecated; use setOperation() instead.', E_USER_DEPRECATED);
        return $this->setOperation($operations, $onSubmit, $type);
    }

    /**
     * @param string $label of exporting file
     * @param string $type export class - @deprecated
     * @throws \InvalidArgumentException
     * @return Export
     */
    public function setExport($label = NULL, $type = '\Grido\Components\Export')
    {
        if ($type !== '\Grido\Components\Export') {
            trigger_error('Parameter $type is deprecated; just create instance of your own type.', E_USER_DEPRECATED);
        }

        $export = new $type($this, $label);
        if (!$export instanceof Components\Export) {
            throw new \InvalidArgumentException('Export must be inherited from \Grido\Components\Export.');
        }

        return $export;
    }

    /** @deprecated */
    public function setExporting($label = NULL, $type = '\Grido\Components\Export')
    {
        trigger_error(__METHOD__ . '() is deprecated; use setExport() instead.', E_USER_DEPRECATED);
        return $this->setExport($label, $type);
    }
}
