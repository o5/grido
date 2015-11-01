<?php

/**
 * Test: DataSources test case.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Components\Columns\Column;

require_once __DIR__ . '/../bootstrap.php';

abstract class DataSourceTestCase extends \Tester\TestCase
{
    const EDITABLE_TEST_ID = 1;
    const EDITABLE_TEST_VALUE = 'New value';
    const EDITABLE_TEST_VALUE_OLD = 'Old value';

    /** @var array GET parameters to request */
    private $params =  [
        'grid-page' => 2,
        'grid-sort' => ['country' => Column::ORDER_ASC],
        'grid-filter' => [
            'name' => 'a',
            'male' => TRUE,
            'country' => 'au',
    ]];

    function testRender()
    {
        Helper::request($this->params);

        ob_start();
            Helper::$grid->render();
        $output = ob_get_clean();

        Assert::matchFile(__DIR__ . "/files/render.expect", $output);
    }

    function testSuggest()
    {
        Helper::$presenter->forceAjaxMode = TRUE;
        $params = $this->params + ['grid-filters-country-query' => 'and', 'do' => 'grid-filters-country-suggest'];
        ob_start();
            Helper::request($params);
        $output = ob_get_clean();
        Assert::same('["Finland","Poland"]', $output);

        $params = ['grid-filters-name-query' => 't', 'do' => 'grid-filters-name-suggest'];
        ob_start();
            Helper::request($params);
        $output = ob_get_clean();
        Assert::same('["Awet","Caitlin","Dragotina","Katherine","Satu","Trommler"]', $output);
    }

    function testSetWhere()
    {
        Helper::request(['grid-filter' => ['tall' => TRUE]]);
        Helper::$grid->getData(FALSE);
        Assert::same(10, Helper::$grid->count);
    }

    function testEditable()
    {
        Helper::$presenter->forceAjaxMode = TRUE;

        $params = [
            'do' => 'grid-columns-firstname-editable',
            'grid-columns-firstname-id' => self::EDITABLE_TEST_ID,
            'grid-columns-firstname-newValue' => self::EDITABLE_TEST_VALUE,
            'grid-columns-firstname-oldValue' => self::EDITABLE_TEST_VALUE_OLD,
        ];
        ob_start();
            Helper::request($params);
        $output = ob_get_clean();

        Assert::same('{"updated":true,"html":"'. self::EDITABLE_TEST_VALUE. '"}', $output);
    }

    function editableCallbackTest($id, $newValue, $oldValue, \Grido\Components\Columns\Editable $column)
    {
        Assert::same(self::EDITABLE_TEST_ID, $id);
        Assert::same(self::EDITABLE_TEST_VALUE, $newValue);
        Assert::same(self::EDITABLE_TEST_VALUE_OLD, $oldValue);
        Assert::same('firstname', $column->name);

        return TRUE;
    }

    function testNullableForeignKey()
    {
        Helper::request();

        ob_start();
            Helper::$grid->render();
        $output = ob_get_clean();

        Assert::same(Helper::$grid->count, 50);
    }

    function testExport()
    {
        Helper::$presenter->forceAjaxMode = FALSE;
        $params = $this->params + ['do' => 'grid-export-export'];

        ob_start();
            Helper::request($params)->send(mock('\Nette\Http\IRequest'), new \Nette\Http\Response);
        $output = ob_get_clean();

        Assert::same(file_get_contents(__DIR__ . "/files/export.expect"), $output);
    }

    function tearDown()
    {
        //cleanup
        Helper::$presenter->forceAjaxMode = FALSE;
    }
}
