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
 */
class Export extends Component implements \Nette\Application\IResponse
{
    const ID = 'export';

    /** @var int */
    protected $fetchLimit = 100000;

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

        $header = array();
        $columns = $this->grid[Column::ID]->getComponents();
        foreach ($columns as $column) {
            $header[] = $escape($column->getLabel());
        }

        $print($header);

        $datasource = $this->grid->getData(FALSE, FALSE, FALSE);
        $iterations = ceil($datasource->getCount() / $this->fetchLimit);
        for ($i = 0; $i < $iterations; $i++) {
            $datasource->limit($i * $this->fetchLimit, $this->fetchLimit);
            $data = $datasource->getData();

            foreach ($data as $item) {
                $row = array();

                foreach ($columns as $column) {
                    $row[] = $escape($column->renderExport($item));
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
