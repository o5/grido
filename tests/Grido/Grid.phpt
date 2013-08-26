<?php

/**
 * Test: Grid - tests for a class's basic behaviour.
 *
 * @author     Petr Bugyík
 * @package    Grido\Test
 * @subpackage Grid
 */

require_once __DIR__ . '/../bootstrap.php';

use \Grido\Components\Columns\Column,
    \Grido\Components\Filters\Filter;

test(function() //setModel()
{
    $grid = new Grid;

    $grid->setModel(mock('Grido\DataSources\IDataSource'));
    Assert::type('Grido\DataSources\IDataSource', $grid->model);

    $grid->setModel(mock('Grido\DataSources\IDataSource'), TRUE);
    Assert::type('Grido\DataSources\Model', $grid->model);

    $grid->setModel(mock('\DibiFluent'));
    Assert::type('Grido\DataSources\Model', $grid->model);

    $grid->setModel(mock('\Nette\Database\Table\Selection'));
    Assert::type('Grido\DataSources\Model', $grid->model);

    $grid->setModel(mock('\Doctrine\ORM\QueryBuilder'));
    Assert::type('Grido\DataSources\Model', $grid->model);

    $grid->setModel(array('TEST' => 'TEST'));
    Assert::type('Grido\DataSources\Model', $grid->model);

    $grid->setModel(mock('\DibiFluent'), TRUE);
    Assert::type('Grido\DataSources\Model', $grid->model);

    Assert::exception(function() use ($grid) {
        $grid->setModel(mock('BAD'));
    }, 'InvalidArgumentException');

    Assert::exception(function() use ($grid) {
        $grid->setModel(mock('BAD'), TRUE);
    }, 'InvalidArgumentException');
});

test(function() //setDefaultPerPage()
{
    $grid = new Grid;

    $perPage = 11;
    $perPageList = $grid->perPageList;
    $perPageList[] = $perPage;
    sort($perPageList);

    $grid->setDefaultPerPage((string) $perPage);
    Assert::same($perPage, $grid->defaultPerPage);
    Assert::same($perPageList, $grid->perPageList);
});

test(function() //setDefaultSort()
{
    $grid = new Grid;

    $grid->setDefaultSort(array('a' => 'ASC', 'b' => 'desc', 'c' => 'Asc', 'd' => Column::DESC));
    Assert::same(array('a' => Column::ASC, 'b' => Column::DESC, 'c' => Column::ASC, 'd' => Column::DESC), $grid->defaultSort);

    Assert::exception(function() use ($grid) {
        $grid->setDefaultSort(array('a' => 'up'));
    }, 'InvalidArgumentException');
});

test(function() //setPerPageList()
{
    $grid = new Grid;

    $grid->addFilterText('test', 'Test');

    $a = array(10, 20);
    $grid->setPerPageList($a);
    Assert::same($a, $grid->perPageList);
    Assert::same(array_combine($a, $a), $grid['form']['count']->items);
});

test(function() //setFilterRenderType()
{
    $grid = new Grid;

    $type = Filter::RENDER_INNER;
    $grid->setFilterRenderType($type);
    Assert::same($type, $grid->filterRenderType);

    $type = Filter::RENDER_OUTER;
    $grid->setFilterRenderType($type);
    Assert::same($type, $grid->filterRenderType);

    $grid->setFilterRenderType('OUTER');
    Assert::same($type, $grid->filterRenderType);

    Assert::exception(function() use ($grid) {
        $grid->setFilterRenderType('INNERR');
    }, 'InvalidArgumentException');
});

test(function() //setPropertyAccessor()
{
    $grid = new Grid;

    $expected = 'Grido\PropertyAccessors\IPropertyAccessor';
    $grid->setPropertyAccessor(mock($expected));
    Assert::type($expected, $grid->propertyAccessor);

    Assert::error(function() use ($grid) {
        $grid->setPropertyAccessor('');
    }, E_RECOVERABLE_ERROR);
});

test(function() //setDefaultFilter()
{
    $grid = new Grid;

    $expected = array('column' => 'value');
    $grid->setDefaultFilter($expected);
    Assert::same($expected, $grid->defaultFilter);

    Assert::error(function() use ($grid) {
        $grid->setDefaultFilter('');
    }, E_RECOVERABLE_ERROR);
});

test(function() //setTranslator()
{
    $grid = new Grid;

    $expected = '\Nette\Localization\ITranslator';
    $grid->setTranslator(mock($expected));
    Assert::type($expected, $grid->translator);

    Assert::error(function() use ($grid) {
        $grid->setTranslator('');
    }, E_RECOVERABLE_ERROR);
});

test(function() //setPaginator()
{
    $grid = new Grid;

    $expected = '\Grido\Components\Paginator';
    $grid->setPaginator(mock($expected));
    Assert::type($expected, $grid->paginator);

    Assert::error(function() use ($grid) {
        $grid->setPaginator('');
    }, E_RECOVERABLE_ERROR);
});

test(function() //setPrimaryKey()
{
    $grid = new Grid;

    $key = 'id';
    $grid->setPrimaryKey($key);
    Assert::same($key, $grid->primaryKey);
});

test(function() //setTemplateFile()
{
    $grid = new Grid;

    $template = __FILE__;
    $grid->setTemplateFile($template);
    Assert::same($template, $grid->template->getFile());
});

test(function() //setRememberState
{
    $grid = new Grid;

    $grid->setRememberState(1);
    Assert::same(TRUE, $grid->rememberState);
});

test(function() //setRowCallback()
{
    $grid = new Grid;

    $rowCallback = array();
    $grid->setRowCallback($rowCallback);
    Assert::same($rowCallback, $grid->rowCallback);

    $rowCallback = function() {};
    $grid->setRowCallback($rowCallback);
    Assert::same($rowCallback, $grid->rowCallback);

    $rowCallback = mock('\Nette\Utils\Callback');
    $grid->setRowCallback($rowCallback);
    Assert::same($rowCallback, $grid->rowCallback);
});

test(function() //setClientSideOptions()
{
    $grid = new Grid;

    $options = array('key' => 'value');
    $grid->setClientSideOptions($options);
    Assert::same($grid->tablePrototype->data['grido-options'], json_encode($options));
});

test(function() //addColumn*()
{
    $grid = new Grid;
    $label = 'Column';

    $name = 'text';
    $grid->addColumnText($name, $label);
    $component = $grid->getColumn($name);
    Assert::type('\Grido\Components\Columns\Text', $component);
    Assert::same($label, $component->label);

    $name = 'mail';
    $grid->addColumnMail($name, $label);
    $component = $grid->getColumn($name);
    Assert::type('\Grido\Components\Columns\Mail', $component);
    Assert::same($label, $component->label);

    $name = 'href';
    $grid->addColumnHref($name, $label);
    $component = $grid->getColumn($name);
    Assert::type('\Grido\Components\Columns\Href', $component);
    Assert::same($label, $component->label);

    $name = 'date';
    $format = \Grido\Components\Columns\Date::FORMAT_DATE;
    $grid->addColumnDate($name, $label, $format);
    $component = $grid->getColumn($name);
    Assert::type('\Grido\Components\Columns\Date', $component);
    Assert::same($label, $component->label);
    Assert::same($format, $component->dateFormat);

    $name = 'number';
    $decimals = 1;
    $decPoint = ',';
    $thousandsSep = '.';
    $grid->addColumnNumber($name, $label, $decimals, $decPoint, $thousandsSep);
    $component = $grid->getColumn($name);
    Assert::type('\Grido\Components\Columns\Number', $component);
    Assert::same($label, $component->label);
    Assert::same(array(
        \Grido\Components\Columns\Number::NUMBER_FORMAT_DECIMALS => $decimals,
        \Grido\Components\Columns\Number::NUMBER_FORMAT_DECIMAL_POINT => $decPoint,
        \Grido\Components\Columns\Number::NUMBER_FORMAT_THOUSANDS_SEPARATOR => $thousandsSep
    ), $component->numberFormat);

    //@deprecated
    Assert::error(function() use ($grid, $label) {
        $name = 'deprecated';
        $grid->addColumn($name, $label, \Grido\Components\Columns\Column::TYPE_DATE);
        $component = $grid->getColumn($name);
        Assert::type('\Grido\Components\Columns\Text', $component);
        Assert::same($label, $component->label);
    }, E_USER_WARNING);

    // getter
    Assert::exception(function() use ($grid) {
        $grid->getColumn('TEST');
    }, 'InvalidArgumentException');
    Assert::same(NULL, $grid->getColumn('TEST', FALSE));
});

test(function() //addFilter*()
{
    $grid = new Grid;
    $label = 'Filter';

    $name = 'text';
    $grid->addFilterText($name, $label);
    $component = $grid->getFilter($name);
    Assert::type('\Grido\Components\Filters\Text', $component);
    Assert::same($label, $component->label);

    $name = 'date';
    $grid->addFilterDate($name, $label);
    $component = $grid->getFilter($name);
    Assert::type('\Grido\Components\Filters\Date', $component);
    Assert::same($label, $component->label);

    $name = 'check';
    $grid->addFilterCheck($name, $label);
    $component = $grid->getFilter($name);
    Assert::type('\Grido\Components\Filters\Check', $component);
    Assert::same($label, $component->label);

    $name = 'select';
    $items = array('one' => 'raz', 'two' => 'dva');
    $grid->addFilterSelect($name, $label, $items);
    $component = $grid->getFilter($name);
    Assert::type('\Grido\Components\Filters\Select', $component);
    Assert::same($label, $component->label);
    Assert::same($items, $component->getControl()->items);

    $name = 'number';
    $grid->addFilterNumber($name, $label);
    $component = $grid->getFilter($name);
    Assert::type('\Grido\Components\Filters\Number', $component);
    Assert::same($label, $component->label);

    $name = 'custom';
    $control = new \Nette\Forms\Controls\TextArea($label);
    $grid->addFilterCustom($name, $control);
    $component = $grid->getFilter($name);
    Assert::type('\Grido\Components\Filters\Custom', $component);
    Assert::type('\Nette\Forms\Controls\TextArea', $component->formControl);

    //@deprecated
    Assert::error(function() use ($grid, $label) {
        $name = 'deprecated';
        $grid->addFilter($name, $label, Grido\Components\Filters\Filter::TYPE_CHECK);
        $component = $grid->getFilter($name);
        Assert::type('\Grido\Components\Filters\Check', $component);
        Assert::same($label, $component->label);
    }, E_USER_WARNING);

    // getter
    Assert::exception(function() use ($grid) {
        $grid->getFilter('TEST');
    }, 'InvalidArgumentException');
    Assert::same(NULL, $grid->getFilter('TEST', FALSE));
});

test(function() //addAction*()
{
    $grid = new Grid;
    $label = 'Action';

    $name = 'href';
    $destination = 'edit';
    $args = array('args');
    $grid->addActionHref($name, $label, $destination, $args);
    $component = $grid->getAction($name);
    Assert::type('\Grido\Components\Actions\Href', $component);
    Assert::same($label, $component->label);
    Assert::same($destination, $component->destination);
    Assert::same($args, $component->arguments);

    $name = 'event';
    $onClick = function() {};
    $grid->addActionEvent($name, $label, $onClick);
    $component = $grid->getAction($name);
    Assert::type('\Grido\Components\Actions\Event', $component);
    Assert::same($label, $component->label);
    Assert::same(array($onClick), $component->onClick);

    //@deprecated
    Assert::error(function() use ($grid, $label, $destination, $args) {
        $name = 'deprecated';
        $grid->addAction($name, $label, \Grido\Components\Actions\Action::TYPE_HREF, $destination, $args);
        $component = $grid->getAction($name);
        Assert::type('\Grido\Components\Actions\Href', $component);
        Assert::same($label, $component->label);
        Assert::same($destination, $component->destination);
        Assert::same($args, $component->arguments);
    }, E_USER_WARNING);

    // getter
    Assert::exception(function() use ($grid) {
        $grid->getAction('TEST');
    }, 'InvalidArgumentException');
    Assert::same(NULL, $grid->getAction('TEST', FALSE));
});

test(function() //setOperations()
{
    $grid = new Grid;

    $operations = array('print' => 'Print', 'delete' => 'Delete');
    $onSubmit = function() {};
    $grid->setOperations($operations, $onSubmit);
    $component = $grid->getOperations();
    Assert::type('\Grido\Components\Operation', $component);
    $componentId = Grido\Components\Operation::ID;
    Assert::same($operations, $grid['form'][$componentId][$componentId]->items);
    Assert::same($component->onSubmit, array($onSubmit));

    // getter
    $grid = new Grid;

    Assert::exception(function() use ($grid) {
        $grid->getOperations();
    }, 'InvalidArgumentException');

    Assert::same(NULL, $grid->getOperations(FALSE));
});

test(function() //setExporting()
{
    $grid = new Grid;
    $label = 'Grid';

    $grid->setExport($label);
    $component = $grid->getExport();
    Assert::type('\Grido\Components\Export', $component);
    Assert::same($label, $component->label);

    // getter
    $grid = new Grid;

    Assert::exception(function() use ($grid) {
        $grid->getExport();
    }, 'InvalidArgumentException');

    Assert::same(NULL, $grid->getExport(FALSE));

    //@deprecated
    Assert::error(function() use ($grid, $label) {
        $grid->setExporting($label);
    }, E_USER_WARNING);
});
