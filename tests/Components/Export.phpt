<?php

/**
 * Test: Export.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Grido\Components\Exports\BaseExport;
use Grido\Components\Exports\CsvExport;
use Tester\Assert,
    Grido\Grid,
    Grido\Tests\Helper,
    Grido\Components\Export,
    Grido\DataSources\ArraySource;

require_once __DIR__ . '/../bootstrap.php';

class Response extends \Nette\Object implements \Nette\Http\IResponse
{
    public static $headers = [];

    function setHeader($name, $value)
    {
        self::$headers[$name] = $value;
        return $this;
    }

    function setCode($code) {}
    function getCode() {}
    function addHeader($name, $value) {}
    function getHeader($header, $default = NULL) {}
    function setContentType($type, $charset = NULL) {}
    function redirect($url, $code = self::S302_FOUND) {}
    function setExpiration($seconds) {}
    function isSent() {}
    function getHeaders() {}
    function setCookie($name, $value, $expire, $path = NULL, $domain = NULL, $secure = NULL, $httpOnly = NULL) {}
    function deleteCookie($name, $path = NULL, $domain = NULL, $secure = NULL) {}
}

class ExportTest extends \Tester\TestCase
{
    function testHasExport()
    {
        $grid = new Grid;
        Assert::false($grid->hasExport());

        $grid->addExport(new CsvExport(), 'csv');
        Assert::false($grid->hasExport());
        Assert::true($grid->hasExport(FALSE));
    }

    function testSetExport()
    {
        $grid = new Grid;
        $label = 'export';

        $grid->addExport(new CsvExport($label), 'csv');
        $component = $grid->getExport('csv');
        Assert::type('\Grido\Components\Exports\BaseExport', $component);
        Assert::same($label, $component->label);

        $grid[BaseExport::ID]->removeComponent($grid->getExport('csv'));
        // getter
        Assert::exception(function() use ($grid) {
            $grid->getExport('csv');
        }, 'Nette\InvalidArgumentException');
    }

    function testHandleExport()
    {
        $this->exportScenario('Testovací export');
    }

    function testLabelGeneration()
    {
        $this->exportScenario();
    }

    private function exportScenario($label = NULL)
    {
        Helper::grid(function(Grid $grid) use ($label) {
            $grid->setModel([
                ['id' => 1, 'name' => 'Lucy', 'country' => 'Switzerland'],
                ['id' => 2, 'name' => "Příliš; žlouťoucký, \"kůň\" \n ďábelsky \tpěl 'ódy", 'country' => 'Switzerland'],
                ['id' => 3, 'name' => 'Silvia', 'country' => 'Switzerland'],
                ['id' => 4, 'name' => 'Mary', 'country' => 'Australia'],
                ['id' => 5, 'name' => 'Michelle', 'country' => 'Australia'],
            ]);

            $grid->setDefaultPerPage(2);
            $grid->addColumnText('name', 'Name')
                ->setSortable();
            $grid->addColumnText('country', 'Country')
                ->setFilterText();
            $grid->addExport(new CsvExport($label), 'csv');
        });

        $params = [
            'do' => 'grid-export-csv-export',
            'grid-sort' => ['name' => \Grido\Components\Columns\Column::ORDER_DESC],
            'grid-filter' => ['country' => 'Switzerland'],
            'grid-page' => 2
        ];

        ob_start();
            Helper::request($params)->send(mock('\Nette\Http\IRequest'), new Response);
        $output = ob_get_clean();
        Assert::same(file_get_contents(__DIR__ . '/files/Export.expect'), $output);

        $label = $label ? ucfirst(\Nette\Utils\Strings::webalize($label)) : 'Grid';

        Assert::same([
            'Content-Encoding' => 'utf-8',
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$label.csv\"",
        ], Response::$headers);
    }

    function testCustomData()
    {
        Helper::grid(function(Grid $grid) {
            $grid->setModel(new ArraySource([
                ['firstname' => 'Satu', 'surname' => 'Tukio', 'card' => 'Visa'],
                ['firstname' => 'Ronald', 'surname' => 'Olivo', 'card' => 'MasterCard'],
                ['firstname' => 'Feorie', 'surname' => 'Hamid', 'card' => 'MasterCard'],
                ['firstname' => 'Hyiab', 'surname' => 'Haylom', 'card' => 'MasterCard'],
                ['firstname' => 'Ambessa', 'surname' => 'Ali', 'card' => 'Visa'],
                ['firstname' => 'Mateo', 'surname' => 'Topić', 'card' => "Příliš; žlouťoucký, \"kůň\" \n ďábelsky \tpěl 'ódy"],
            ]));

            $grid->addColumnText('firstname', 'Name')
                ->setSortable();

            $grid->addExport(new CsvExport(), 'csv')
                ->setHeader(['"Jméno"', "Příjmení\t", "Karta\n", 'Jméno,Příjmení'])
                ->setCustomData(function(ArraySource $source) {
                    $data = $source->getData();
                    $outData = [];
                    foreach ($data as $item) {
                        $outData[] = [
                            $item['firstname'],
                            $item['surname'],
                            $item['card'],
                            $item['firstname'] . ',' .$item['surname'],
                        ];
                    }
                    return $outData;
                });
        });

        $params = ['do' => 'grid-export-csv-export'];

        ob_start();
            Helper::request($params)->send(mock('\Nette\Http\IRequest'), new Response);
        $output = ob_get_clean();
        Assert::same(file_get_contents(__DIR__ . '/files/Export.custom.expect'), $output);
    }
}

run(__FILE__);
