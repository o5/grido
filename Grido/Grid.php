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
    Grido\Components\Paginator;

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
class Grid extends Components\Container
{
    /***** DEFAULTS ****/
    const BUTTONS = 'buttons';

    const CLIENT_SIDE_OPTIONS = 'grido-options';

    /** @var int @persistent */
    public $page = 1;

    /** @var int @persistent */
    public $perPage;

    /** @var array @persistent */
    public $sort = array();

    /** @var array @persistent */
    public $filter = array();

    /** @var array event on all grid's components registered */
    public $onRegistered;

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
     * @param bool $state
     * @return Grid
     */
    public function setRememberState($state = TRUE)
    {
        $this->getPresenter(); //component must be attached to presenter
        $this->getRememberSession(TRUE); //start session if not
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
        $this->getTablePrototype()->data[self::CLIENT_SIDE_OPTIONS] = json_encode($options);
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
     * @param bool $forceStart - if TRUE, session will be started if not
     * @return \Nette\Http\SessionSection|NULL
     */
    public function getRememberSession($forceStart = FALSE)
    {
        $presenter = $this->getPresenter();
        $session = $presenter->getSession();

        if (!$session->isStarted() && $forceStart) {
            $session->start();
        }

        return $session->isStarted()
            ? $session->getSection($presenter->getName() . '\\' . ucfirst($this->getName()))
            : NULL;
    }

    /**
     * Returns table html element of grid.
     * @return \Nette\Utils\Html
     */
    public function getTablePrototype()
    {
        if ($this->tablePrototype === NULL) {
            $this->tablePrototype = \Nette\Utils\Html::el('table');
            $this->tablePrototype->id($this->getName())
                ->class[] = 'table table-striped table-hover';
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
        try {
            $primaryValue = $this->getPropertyAccessor()->getProperty($row, $this->getPrimaryKey());
        } catch (\Exception $e) {
            $primaryValue = NULL;
        }

        $tr = \Nette\Utils\Html::el('tr');
        $primaryValue ? $tr->class[] = "grid-row-$primaryValue" : NULL;

        if ($this->rowCallback) {
            $tr = callback($this->rowCallback)->invokeArgs(array($row, $tr));
        }

        return $tr;
    }

    /**
     * Returns client-side options.
     * @return array
     */
    public function getClientSideOptions()
    {
        return (array) json_decode($this->getTablePrototype()->data[self::CLIENT_SIDE_OPTIONS]);
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
        if ($session && $this->getPresenter()->isSignalReceiver($this)) {
            $session->remove();
        } elseif ($session && !$params && $session->params) {
            $params = (array) $session->params;
        }

        parent::loadState($params);
    }

    /**
     * Saves state informations for next request.
     * @param array $params
     * @param \Nette\Application\UI\PresenterComponentReflection $reflection (internal, used by Presenter)
     * @internal
     */
    public function saveState(array &$params, $reflection = NULL)
    {
        $this->onRegistered && $this->onRegistered($this);
        return parent::saveState($params, $reflection);
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
        $sessionFilter = $this->rememberState
            ? isset($this->getRememberSession(TRUE)->params['filter'])
                ? $this->getRememberSession(TRUE)->params['filter']
                : array()
            : array();

        foreach ($values as $name => $value) {
            $value = (string) $value; //maybe this could be removed
            if ($value != '' || isset($this->defaultFilter[$name]) || isset($sessionFilter[$name])) {
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

        if ($session = $this->getRememberSession()) {
            $session->remove();
        }

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
            $this->presenter->payload->grido = TRUE;
            $this->invalidateControl();
        } else {
            $this->redirect('this');
        }
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

        if ($this->onRender) {
            $this->onRender($this);
        }

        $this->template->data = $data;
        $this->template->form = $form = $this['form'];
        $this->template->paginator = $this->paginator;

        $form['count']->setValue($this->getPerPage());

        $this->template->render();
    }

    protected function saveRememberState()
    {
        if ($this->rememberState) {
            $session = $this->getRememberSession(TRUE);
            $params = array_keys($this->getReflection()->getPersistentParams());
            foreach($params as $param) {
                $session->params[$param] = $this->$param;
            }
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
                if (!isset($this->defaultSort[$column])) {
                    trigger_error("Column with name '$column' does not exist.", E_USER_NOTICE);
                    break;
                }

            } elseif (!$component->isSortable()) {
                if (isset($this->defaultSort[$column])) {
                    $component->setSortable();
                } else {
                    trigger_error("Column with name '$column' is not sortable.", E_USER_NOTICE);
                    break;
                }
            }

            if (!in_array($dir, array(Column::ORDER_ASC, Column::ORDER_DESC))) {
                if ($dir == '' && isset($this->defaultSort[$column])) {
                    unset($this->sort[$column]);
                    break;
                }

                trigger_error("Dir '$dir' is not allowed.", E_USER_NOTICE);
                break;
            }

            $sort[$component ? $component->column : $column] = $dir == Column::ORDER_ASC ? 'ASC' : 'DESC';
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
}
