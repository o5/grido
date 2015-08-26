<?php

/**
 * Test: Export - handleExport()
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

use Tester\Assert,
    Grido\Grid;

require_once __DIR__ . '/../bootstrap.php';

class Response extends \Nette\Object implements \Nette\Http\IResponse
{
    public static $headers = array();

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

test(function() {
    Helper::grid(function(Grid $grid) {
        $grid->setModel(array(
            array('id' => 1, 'name' => 'Lucy', 'country' => 'Switzerland'),
            array('id' => 2, 'name' => 'Příliš žlouťoucký kůň ďábelsky pěl ódy', 'country' => 'Switzerland'),
            array('id' => 3, 'name' => 'Silvia', 'country' => 'Switzerland'),
            array('id' => 4, 'name' => 'Mary', 'country' => 'Australia'),
            array('id' => 5, 'name' => 'Michelle', 'country' => 'Australia'),
        ));

        $grid->setDefaultPerPage(2);
        $grid->addColumnText('name', 'Name')
            ->setSortable();
        $grid->addColumnText('country', 'Country')
            ->setFilterText();
        $grid->setExport();
    });

    $params = array(
        'do' => 'grid-export-export',
        'grid-sort' => array('name' => \Grido\Components\Columns\Column::ORDER_DESC),
        'grid-filter' => array('country' => 'Switzerland'),
        'grid-page' => 2
    );

    ob_start();
        Helper::request($params)->send(mock('\Nette\Http\IRequest'), new Response);
    $output = ob_get_clean();
    Assert::same(file_get_contents(__DIR__ . '/files/Export.handleExport.expect'), $output);

    Assert::same(array(
	'Content-Encoding' => 'UTF-16LE',
	'Content-Length' => 206,
	'Content-Type' => 'text/csv; charset=UTF-16LE',
	'Content-Disposition' => 'attachment; filename="Grid.csv"; filename*=utf-8\'\'Grid.csv',
    ), Response::$headers);
});
