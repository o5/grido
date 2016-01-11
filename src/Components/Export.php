<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\Components;

use Grido\Grid;
use Grido\Components\Columns\Column;
use Nette\Utils\Strings;

/**
 * Exporting data to CSV.
 *
 * @package     Grido
 * @subpackage  Components
 * @author      Petr Bugyík
 *
 * @property int $fetchLimit
 * @property-write array $header
 * @property-write callable $customData
 */
class Export extends Component implements \Nette\Application\IResponse
{
    const ID = 'export';

    /** @var int */
    protected $fetchLimit = 100000;

    /** @var array */
    protected $header = array();

    /** @var callable */
    protected $customData;

    /**
     * @param Grid $grid
     * @param string $label
     */
    public function __construct(Grid $grid, $label = NULL)
    {
        $this->grid = $grid;
        $this->label = $label;

        $grid->addComponent($this, self::ID);
    }

    /**
     * @return void
     */
    protected function printCsv()
    {
        $escape = function($value) {
            return preg_match("~[\"\n,;\t]~", $value) || $value === ""
                ? '"' . str_replace('"', '""', $value) . '"'
                : $value;
        };

        $print = function(array $row) {
            print implode(',', $row) . "\n";
        };

        $columns = $this->grid[Column::ID]->getComponents();

        $header = array();
        $headerItems = $this->header ? $this->header : $columns;
        foreach ($headerItems as $column) {
            $header[] = $this->header
                ? $escape($column)
                : $escape($column->getLabel());
        }

        $print($header);

        $datasource = $this->grid->getData(FALSE, FALSE, FALSE);
        $iterations = ceil($datasource->getCount() / $this->fetchLimit);
        for ($i = 0; $i < $iterations; $i++) {
            $datasource->limit($i * $this->fetchLimit, $this->fetchLimit);
            $data = $this->customData
                ? call_user_func_array($this->customData, array($datasource))
                : $datasource->getData();

            foreach ($data as $items) {
                $row = array();

                $columns = $this->customData
                    ? $items
                    : $columns;

                foreach ($columns as $column) {
                    $row[] = $this->customData
                        ? $escape($column)
                        : $escape($column->renderExport($items));
                }

                $print($row);
            }
        }
    }

    /**
     * Sets a limit which will be used in order to retrieve data from datasource.
     * @param int $limit
     * @return \Grido\Components\Export
     */
    public function setFetchLimit($limit)
    {
        $this->fetchLimit = (int) $limit;
        return $this;
    }

    /**
     * @return int
     */
    public function getFetchLimit()
    {
        return $this->fetchLimit;
    }

    /**
     * Sets a custom header of result CSV file (list of field names).
     * @param array $header
     * @return \Grido\Components\Export
     */
    public function setHeader(array $header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * Sets a callback to modify output data. This callback must return a list of items. (array) function($datasource)
     * DEBUG? You probably need to comment lines started with $httpResponse->setHeader in Grido\Components\Export.php
     * @param callable $callback
     * @return \Grido\Components\Export
     */
    public function setCustomData($callback)
    {
        $this->customData = $callback;
        return $this;
    }

    /**
     * @internal
     */
    public function handleExport()
    {
        $this->grid->onRegistered && $this->grid->onRegistered($this->grid);
        $this->grid->presenter->sendResponse($this);
    }

    /*************************** interface \Nette\Application\IResponse ***************************/

    /**
     * Sends response to output.
     * @param \Nette\Http\IRequest $httpRequest
     * @param \Nette\Http\IResponse $httpResponse
     * @return void
     */
    public function send(\Nette\Http\IRequest $httpRequest, \Nette\Http\IResponse $httpResponse)
    {
        $encoding = 'utf-8';
        $label = $this->label
            ? ucfirst(Strings::webalize($this->label))
            : ucfirst($this->grid->name);

        $httpResponse->setHeader('Content-Encoding', $encoding);
        $httpResponse->setHeader('Content-Type', "text/csv; charset=$encoding");
        $httpResponse->setHeader('Content-Disposition', "attachment; filename=\"$label.csv\"");

        print chr(0xEF) . chr(0xBB) . chr(0xBF); //UTF-8 BOM
        $this->printCsv();
    }
}
