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
    Grido\Components\Export,
    Grido\Components\Paginator;

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
     * Sets a model that implements the interface Grido\DataSources\IDataSource
     * or data-source object DibiFluent, Nette\Database\Table\Selection.
     * @param mixed $model
     * @throws \InvalidArgumentException
     * @return Grid
     */
    public function setModel($model)
    {
        if ($model instanceof \DibiFluent) {
            $model = new DataSources\DibiFluent($model);
        } elseif ($model instanceof \Nette\Database\Table\Selection) {
            $model = new DataSources\NetteDatabase($model);
        } elseif (is_array($model)) {
            $model = new DataSources\ArraySource($model);
        } elseif (!$model instanceof DataSources\IDataSource) {
            throw new \InvalidArgumentException('Model must be implemented \Grido\DataSources\IDataSource.');
        }

        $this->model = $model;
        return $this;
    }

    /**
     * Sets a property accesor that implements the interface Grido\PropertyAccessors\IPropertyAccessor
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
            $this->count = $this->model->call('getCount');
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
        return $this->perPage === NULL ? $this->defaultPerPage : $this->perPage;
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

            $this->data = $this->model->call('getData');

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

        if ($this->hasFilters()) {
            $this->filterRenderType = $this->hasActions()
                ? Filter::RENDER_INNER
                : Filter::RENDER_OUTER;

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
     * @return IModel
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
      * @param array
      * @return void
      */
    public function loadState(array $params)
    {
        $this->loadRememberState($params);

        parent::loadState($params);

        if($this->perPage !== NULL && !in_array($this->perPage, $this->perPageList)) {
            $this->perPage = NULL;
            $this->reload();
        }
    }

    /**
     * Loads state informations from session.
     * @param array $params
     */
    protected function loadRememberState(array &$params)
    {
        $session = $this->getRememberSession();
        if ($this->presenter->isSignalReceiver($this)) {
            $session->remove();
        } elseif (!$params && $session->params) {
            $params = (array) $session->params;
        }
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
     * @param \Nette\Application\UI\Form $form
     */
    public function handleForm(\Nette\Application\UI\Form $form)
    {
        //filter handling
        if ($form[self::BUTTONS]['search']->isSubmittedBy()) {
            $values = $form->values;
            foreach ($values[Filter::ID] as $name => $value) {
                $filter = $this->getFilter($name);
                $clearDefault = isset($this->defaultFilter[$name]);

                if ($value != '' || $clearDefault) {
                    $this->filter[$name] = $filter->changeValue($value);
                } elseif (isset($this->filter[$name])) {
                    unset($this->filter[$name]);
                }
            }

        //reset button handling
        } elseif ($form[self::BUTTONS]['reset']->isSubmittedBy()) {
            $this->sort = array();
            $this->filter = array();
            $this->perPage = NULL;
            $form->setValues(array(Filter::ID => $this->defaultFilter), TRUE);

        //operations handling
        } elseif ($this->hasOperations() && $form[self::BUTTONS][Operation::ID]->isSubmittedBy()) {
            $this->addCheckers($this->getData());

            $values = $form[Operation::ID]->values;
            if (empty($values[Operation::ID])) {
                $this->reload();
            }
            $ids = array();
            $operation = $values[Operation::ID];
            unset($values[Operation::ID]);
            foreach ($values as $key => $val) {
                if ($val) {
                    $ids[] = $key;
                }
            }
            $this[Operation::ID]->onSubmit($operation, $ids);

        //change items per page handling
        } elseif ($form[self::BUTTONS]['perPage']->isSubmittedBy()) {
            $perPage = (int) $form['count']->value;
            $this->perPage = $perPage == $this->defaultPerPage ? NULL : $perPage;
        }

        $this->page = 1;
        $this->reload();
    }

    /**
     * @internal
     * @param string $name - filter name
     * @param string $query - value from input
     * @throws \InvalidArgumentException
     */
    public function handleSuggest($name, $query)
    {
        $filter = $this->getFilter($name, FALSE);
        if (!$this->presenter->isAjax() || !$filter || $filter->type != Filter::TYPE_TEXT) {
            $this->presenter->terminate();
        }

        $actualFilter = $this->getActualFilter();
        if (isset($actualFilter[$name])) {
            unset($actualFilter[$name]);
        }
        $conditions = $this->_applyFiltering($actualFilter);

        if ($filter->suggestsCallback) {
            $items = callback($this->suggestsCallback)->invokeArgs(array($query, $conditions));

        } elseif (method_exists($this->model, 'suggest')) {
            $conditions[] = $filter->makeFilter($query);
            $items = $this->model->call('suggest', key($filter->getColumns()), $conditions);

        } else {
            throw new \InvalidArgumentException('Set suggest callback or implement method in model.');
        }

        print \Nette\Utils\Json::encode($items);
        $this->presenter->terminate();
    }

    /**
     * @internal
     * @param string $type
     */
    public function handleExport($type)
    {
        if ($export = $this->getComponent(Export::ID, FALSE)) {
            $this->presenter->sendResponse($export);
            $this->presenter->terminate();
        } else {
            trigger_error("Exporting is not allowed.", E_USER_NOTICE);
        }
    }

    /**
     * Refresh wrapper.
     * @return void
     */
    protected function reload()
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
     * @return bool
     */
    public function hasActions()
    {
        if ($this->hasActions === NULL) {
            $container = $this->getComponent(Action::ID, FALSE);
            $this->hasActions = $container && count($container->getComponents()) > 0;
        }

        return $this->hasActions;
    }

    /**
     * @internal
     * @return bool
     */
    public function hasFilters()
    {
        if ($this->hasFilters === NULL) {
            $container = $this->getComponent(Filter::ID, FALSE);
            $this->hasFilters = $container && count($container->getComponents()) > 0;
        }

        return $this->hasFilters;
    }

    /**
     * @internal
     * @return bool
     */
    public function hasOperations()
    {
        if ($this->hasOperations === NULL) {
            $this->hasOperations = $this->getComponent(Operation::ID, FALSE);
        }

        return $this->hasOperations;
    }

    /**
     * @internal
     * @return bool
     */
    public function hasExporting()
    {
        if ($this->hasExporting === NULL) {
            $this->hasExporting = $this->getComponent(Export::ID, FALSE);
        }

        return $this->hasExporting;
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
        $data = $this->getData();
        $this->addCheckers($data);

        $this->template->paginator = $this->paginator;
        $this->template->data = $data;

        $this->onRender($this);
        $this->saveRememberState();
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
            $this->model->call('filter', $condition);
        }
    }

    /**
     * @param array $filter
     * @return array
     */
    protected function _applyFiltering(array $filter)
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
                trigger_error("Dir '$dir' is not allowed.", E_USER_NOTICE);
                break;
            }

            $sort[$component->column] = $dir == Column::ASC ? 'ASC' : 'DESC';
        }

        if ($sort) {
            $this->model->call('sort', $sort);
        }
    }

    protected function applyPaging()
    {
        $paginator = $this->getPaginator()
            ->setItemCount($this->getCount())
            ->setPage($this->page);

        $this['form']['count']->setValue($this->getPerPage());
        $this->model->call('limit', $paginator->getOffset(), $paginator->getLength());
    }

    /**
     * @param array $data
     */
    protected function addCheckers($data)
    {
        if ($this->hasOperations()) {
            $operation = $this['form'][Operation::ID];
            if (count($operation->getComponents()) == 1) {
                $pk = $this[Operation::ID]->getPrimaryKey();
                foreach ($data as $item) {
                    $operation->addCheckbox($this->getPropertyAccessor()->getProperty($item, $pk));
                }
            }
        }
    }

    protected function createComponentForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->setTranslator($this->getTranslator());
        $form->setMethod(\Nette\Application\UI\Form::GET);

        $buttons = $form->addContainer(self::BUTTONS);
        $buttons->addSubmit('search', 'Search');
        $buttons->addSubmit('reset', 'Reset');
        $buttons->addSubmit('perPage', 'Items per page');

        $form->addSelect('count', 'Count', array_combine($this->perPageList, $this->perPageList))
            ->controlPrototype->attrs['title'] = $this->getTranslator()->translate('Items per page');
        $form->onSuccess[] = callback($this, 'handleForm');

        return $form;
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
     * @return \Grido\Components\Columns\Date
     */
    public function addColumnDate($name, $label)
    {
        return new Components\Columns\Date($this, $name, $label);
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
     * @param string $label
     * @param string $type starting constants with Filter::TYPE_
     * @param mixed $optional if type is select, then this it items for select
     * @throws \InvalidArgumentException
     * @return Filter
     */
    public function addFilter($name, $label, $type = Filter::TYPE_TEXT, $optional = NULL)
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
