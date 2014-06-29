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
require_once __DIR__ . '/../Helper.inc.php';

abstract class DataSourceTestCase extends \Tester\TestCase
{
    /** @var array GET parameters to request */
    private $params =  array(
        'grid-page' => 2,
        'grid-sort' => array('country' => Column::ORDER_ASC),
        'grid-filter' => array(
            'name' => 'a',
            'male' => TRUE,
            'country' => 'au',
    ));

    function testRender()
    {
        Helper::request($this->params);

        ob_start();
            Helper::$grid->render();
        $output = ob_get_clean();

        //@todo - resolve this wtf? different sorting (dibi and nette db) vs (doctrine and array source)
        $type = in_array(get_called_class(), array('Grido\Tests\ArraySourceTest', 'Grido\Tests\DoctrineTest')) ? 2 : 1;
        $type = PHP_VERSION_ID >= 50511 ? 2 : $type;
        Assert::matchFile(__DIR__ . "/files/render.$type.expect", $output);
    }

    function testSuggest()
    {
        Helper::$presenter->forceAjaxMode = TRUE;
        $params = $this->params + array('grid-filters-country-query' => 'and', 'do' => 'grid-filters-country-suggest');
        ob_start();
            Helper::request($params);
        $output = ob_get_clean();
        Assert::same('["Finland","Poland"]', $output);

        $params = array('grid-filters-name-query' => 't', 'do' => 'grid-filters-name-suggest');
        ob_start();
            Helper::request($params);
        $output = ob_get_clean();
        Assert::same('["Trommler","Awet","Caitlin","Dragotina","Katherine","Satu"]', $output);
    }

    function testSetWhere()
    {
        Helper::request(array('grid-filter' => array('tall' => TRUE)));
        Helper::$grid->getData(FALSE);
        Assert::same(10, Helper::$grid->count);
    }

    function testExport()
    {
        Helper::$presenter->forceAjaxMode = FALSE;
        $params = $this->params + array('do' => 'grid-export-export');

        ob_start();
            Helper::request($params)->send(mock('\Nette\Http\IRequest'), new \Nette\Http\Response);
        $output = ob_get_clean();

        //@todo - resolve this wtf? different sorting (dibi, nette db, doctrine) vs (array source)
        $type = in_array(get_called_class(), array('Grido\Tests\ArraySourceTest')) ? '.array' : '';
        $type = PHP_VERSION_ID >= 50511 ? '.array' : $type;
        Assert::same(file_get_contents(__DIR__ . "/files/export$type.expect"), $output);
    }
}
