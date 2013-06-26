<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
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
 * @property-read callback $rowCallback
 * @property-write bool $rememberState
 * @property-write array $defaultPerPage
 * @property-write string $templateFile
 * @property array $defaultFilter
 * @property array $defaultSort
 * @property array $perPageList
 * @property \Nette\Localization\ITranslator $translator
 * @property Paginator $paginator
 * @property string $primaryKey
 * @property string $filterRenderType
 * @property DataSources\IDataSource $model
 * @property PropertyAccessors\IPropertyAccessor $propertyAccessor
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

    /** @var callback $rowCallback - callback returns tr html element; function($row, Html $tr) */
    protected $rowCallback;

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
    protected $hasFilters, $hasActions, $hasOperations, $hasExporting;

    /**
     * Sets a model that implements the interface Grido\DataSources\IDataSource or data-source object.
     * @param mixed $model
     * @param bool $forceWrapper
     * @throws \InvalidArgumentException
     * @return Grid
     */
    public function setModel($model, $forceWrapper = FALSE)
    {
        if ($model instanceof DataSources\IDataSource && $forceWrapper === FALSE) {
            $this->model = $model;
        } else {
            $this->model = new DataSources\Model($model);
        }

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
     * Sets default per page.
     * @param int $perPage
     * @return Grid
     */
    public function setDefaultPerPage($perPage)
    {
        $this->defaultPerPage = (int) $perPage;

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
        $this->defaultFilter = $this->defaultFilter
            ? array_merge($this->defaultFilter, $filter)
            : $filter;

        return $this;
    }

    /**
     * Sets default sorting.
     * @param array $sort
     * @return Grid
     */
    public function setDefaultSort(array $sort)
    {
        static $replace = array('asc' => Column::ASC, 'desc' => Column::DESC);

        foreach ($sort as $column => $dir) {
            $this->defaultSort[$column] = strtr(strtolower($dir), $replace);
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
        if ($this->hasFilters(FALSE) || $this->hasOperations(FALSE)) {
            trigger_error("This call may not be relevant after setting some filters or operations.", E_USER_NOTICE);
        }

        $this->perPageList = $perPageList;
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
     * Returns items per page.
     * @return int
     */
    public function getPerPage()
    {
        $perPage = $this->perPage === NULL
            ? $this->defaultPerPage
            : $this->perPage;

        if ($perPage !== NULL && !in_array($perPage, $this->perPageList)) {
            trigger_error("Items per page is out of range.", E_USER_NOTICE);
            $perPage = $this->defaultPerPage;
        }

        return $perPage;
    }

    /**
     * Returns column component.
     * @param string $name
     * @param bool $need
     * @return Column
     */
    public function getColumn($name, $need = TRUE)
    {
        return $this[Column::ID]->getComponent($name, $need);
    }

    /**
     * Returns filter component.
     * @param string $name
     * @param bool $need
     * @return Filter
     */
    public function getFilter($name, $need = TRUE)
    {
        return $this[Filter::ID]->getComponent($name, $need);
    }

    /**
     * Returns action component.
     * @param string $name
     * @param bool $need
     * @return Action
     */
    public function getAction($name, $need = TRUE)
    {
        return $this[Action::ID]->getComponent($name, $need);
    }

    /**
     * Returns operations component.
     * @param bool $need
     * @return Operation
     */
    public function getOperations($need = TRUE)
    {
        return $this->getComponent(Operation::ID, $need);
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
     * @throws \Exception
     * @return array
     */
    public function getData($applyPaging = TRUE)
    {
        if ($this->model === NULL) {
            throw new \Exception('Model cannot be empty, please use method $grid->setModel().');
        }

        if ($this->data === NULL) {
            $this->applyFiltering();
            $this->applySorting();

            if ($applyPaging) {
                $this->applyPaging();
            }

            $this->data = $this->model->getData();

            if ($this->data && !in_array($this->page, range(1, $this->getPaginator()->pageCount))) {
                trigger_error("Page is out of range.", E_USER_NOTICE);
                $this->page = 1;
            }

            if ($this->onFetchData) {
                $this->onFetchData($this);
            }
        }

        return $this->data;
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
        return $this->presenter->getSession($this->presenter->name . '\\' . ucfirst($this->name));
    }

    /**
     * @internal
     * @return string
     */
    public function getFilterRenderType()
    {
        if ($this->filterRenderType !== NULL) {
            return $this->filterRenderType;
        }

        $this->filterRenderType = Filter::RENDER_OUTER;

        if ($this->hasFilters() && $this->hasActions()) {
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
     * @internal
     * @return DataSources\IDataSource
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @internal
     * @return PropertyAccessors\IPropertyAccessor
     */
    public function getPropertyAccessor()
    {
        if ($this->propertyAccessor === NULL) {
            $this->propertyAccessor = new PropertyAccessors\ArrayObjectAccessor;
        }

        return $this->propertyAccessor;
    }

    /**
     * @internal
     * @return Paginator
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
     * @internal
     * @param mixed $row item from db
     * @return \Nette\Utils\Html
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
      * @internal
      * @param array $params
      */
    public function loadState(array $params)
    {
        //loads state from session
        $session = $this->getRememberSession();
        if ($this->presenter->isSignalReceiver($this)) {
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
     * @internal
     * @param int $page
     */
    public function handlePage($page)
    {
        $this->reload();
    }

    /**
     * @internal
     * @param array $sort
     */
    public function handleSort(array $sort)
    {
        $this->page = 1;
        $this->reload();
    }

    /**
     * @internal
     * @param \Nette\Forms\Controls\SubmitButton $button
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
     * @internal
     * @param \Nette\Forms\Controls\SubmitButton $button
     */
    public function handleReset(\Nette\Forms\Controls\SubmitButton $button)
    {
        $this->sort = array();
        $this->perPage = NULL;
        $this->filter = array();
        $this->getRememberSession()->remove();
        $button->form->setValues(array(Filter::ID => $this->defaultFilter), TRUE);

        $this->page = 1;
        $this->reload();
    }

    /**
     * @internal
     * @param \Nette\Forms\Controls\SubmitButton $button
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
     * @internal
     * @return void
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
     * @internal
     * @param bool $useCache
     * @return bool
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
     * @internal
     * @param bool $useCache
     * @return bool
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
     * @internal
     * @param bool $useCache
     * @return bool
     */
    public function hasOperations($useCache = TRUE)
    {
        $hasOperations = $this->hasOperations;

        if ($hasOperations === NULL || $useCache === FALSE) {
            $hasOperations = $this->getComponent(Operation::ID, FALSE);
            $this->hasOperations = $useCache ? $hasOperations : NULL;
        }

        return $hasOperations;
    }

    /**
     * @internal
     * @param bool $useCache
     * @return bool
     */
    public function hasExporting($useCache = TRUE)
    {
        $hasExporting = $this->hasExporting;

        if ($hasExporting === NULL || $useCache === FALSE) {
            $hasExporting = $this->getComponent(Export::ID, FALSE);
            $this->hasExporting = $useCache ? $hasExporting : NULL;
        }

        return $hasExporting;
    }

    /**********************************************************************************************/

    /**
     * @internal
     * @param string $class
     * @return \Nette\Templating\FileTemplate
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
        $this->saveRememberState();
        $data = $this->getData();

        $this->template->paginator = $this->paginator;
        $this->template->data = $data;

        $this->onRender($this);
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
        $conditions = $this->_applyFiltering($this->getActualFilter());
        foreach ($conditions as $condition) {
            $this->model->filter($condition);
        }
    }

    /**
     * @internal
     * @param array $filter
     * @return array
     */
    public function _applyFiltering(array $filter)
    {
        $conditions = array();
        if ($filter && $this->hasFilters()) {
            $this['form']->setDefaults(array(Filter::ID => $filter));

            foreach ($filter as $column => $value) {
                $component = $this->getFilter($column, FALSE);
                if ($component) {
                    if ($condition = $component->makeFilter($value)) {
                        $conditions[] = $condition;
                    } else {
                        $conditions[] = array('0 = 1'); //result data must be null
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
                trigger_error("Column with name '$column' is not sortable.", E_USER_NOTICE);
                break;
            } elseif (!in_array($dir, array(Column::ASC, Column::DESC))) {

                if ($dir == '' && isset($this->defaultSort[$column])) {
                    unset($this->sort[$column]);
                    break;
                }

                trigger_error("Dir '$dir' is not allowed.", E_USER_NOTICE);
                break;
            }

            $sort[$component->column] = $dir == Column::ASC ? 'ASC' : 'DESC';
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

        $this['form']['count']->setValue($this->getPerPage());
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

        $form->addSelect('count', 'Count', array_combine($this->perPageList, $this->perPageList))
            ->controlPrototype->attrs['title'] = $this->getTranslator()->translate('Items per page');
    }

    /********************************* Components *************************************************/

    /**
     * @param string $name
     * @param string $label
     * @return \Grido\Components\Columns\Text
     */
    public function addColumnText($name, $label)
    {
        return new Components\Columns\Text($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @return \Grido\Components\Columns\Mail
     */
    public function addColumnMail($name, $label)
    {
        return new Components\Columns\Mail($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @return \Grido\Components\Columns\Href
     */
    public function addColumnHref($name, $label)
    {
        return new Components\Columns\Href($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $dateFormat
     * @return \Grido\Components\Columns\Date
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
     * @return \Grido\Components\Columns\Number
     */
    public function addColumnNumber($name, $label, $decimals = NULL, $decPoint = NULL, $thousandsSep = NULL)
    {
        return new Components\Columns\Number($this, $name, $label, $decimals, $decPoint, $thousandsSep);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $type starting constants with Column::TYPE_
     * @throws \InvalidArgumentException
     * @return Column
     */
    public function addColumn($name, $label, $type = Column::TYPE_TEXT)
    {
        $column = new $type($this, $name, $label);
        if (!$column instanceof Column) {
            throw new \InvalidArgumentException('Column must be inherited from \Grido\Components\Columns\Column.');
        }

        return $column;
    }

    /**********************************************************************************************/

    /**
     * @param string $name
     * @param string $label
     * @return \Grido\Components\Filters\Text
     */
    public function addFilterText($name, $label)
    {
        return new Components\Filters\Text($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @return \Grido\Components\Filters\Date
     */
    public function addFilterDate($name, $label)
    {
        return new Components\Filters\Date($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @return \Grido\Components\Filters\Check
     */
    public function addFilterCheck($name, $label)
    {
        return new Components\Filters\Check($this, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $items
     * @return \Grido\Components\Filters\Select
     */
    public function addFilterSelect($name, $label, array $items = NULL)
    {
        return new Components\Filters\Select($this, $name, $label, $items);
    }

    /**
     * @param string $name
     * @param string $label
     * @return \Grido\Components\Filters\Number
     */
    public function addFilterNumber($name, $label)
    {
        return new Components\Filters\Number($this, $name, $label);
    }

    /**
     * @param string $name
     * @param \Nette\Forms\IControl $formControl
     * @return \Grido\Components\Filters\Custom
     */
    public function addFilterCustom($name, \Nette\Forms\IControl $formControl)
    {
        return new Components\Filters\Custom($this, $name, NULL, $formControl);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $type starting constants with Filter::TYPE_
     * @param mixed $optional if type is select, then this is items for select
     * @throws \InvalidArgumentException
     * @return Filter
     */
    public function addFilter($name, $label = NULL, $type = Filter::TYPE_TEXT, $optional = NULL)
    {
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
     * @return \Grido\Components\Actions\Href
     */
    public function addActionHref($name, $label, $destination = NULL, array $args = NULL)
    {
        return new Components\Actions\Href($this, $name, $label, $destination, $args);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $type starting constants with Action::TYPE_
     * @param string $destination - first param for method $presenter->link()
     * @param array $args - second param for method $presenter->link()
     * @throws \InvalidArgumentException
     * @return Action
     */
    public function addAction($name, $label, $type = Action::TYPE_HREF, $destination = NULL, array $args = NULL)
    {
        $action = new $type($this, $name, $label, $destination, $args);
        if (!$action instanceof Action) {
            throw new \InvalidArgumentException('Action must be inherited from \Grido\Components\Actions\Action.');
        }

        return $action;
    }

    /**********************************************************************************************/

    /**
     * @param array $operations
     * @param callback $onSubmit - callback after operation submit
     * @param string $type operation class
     * @throws \InvalidArgumentException
     * @return Operation
     */
    public function setOperations($operations, $onSubmit, $type = '\Grido\Components\Operation')
    {
        $operation = new $type($this, $operations, $onSubmit);
        if (!$operation instanceof Components\Operation) {
            throw new \InvalidArgumentException('Operation must be inherited from \Grido\Components\Operation.');
        }

        return $operation;
    }

    /**
     * @param string $name of exporting file
     * @param string $type export class
     * @throws \InvalidArgumentException
     * @return Export
     */
    public function setExporting($name = NULL, $type = '\Grido\Components\Export')
    {
        $export = new $type($this, $name ? $name : ucfirst($this->name));
        if (!$export instanceof Components\Export) {
            throw new \InvalidArgumentException('Export must be inherited from \Grido\Components\Export.');
        }

        return $export;
    }
}
