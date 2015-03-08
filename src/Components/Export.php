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

/**
 * Exporting data to CSV.
 *
 * @package     Grido
 * @subpackage  Components
 * @author      Petr Bugyík
 */
class Export extends Component implements \Nette\Application\IResponse
{

    const ID = 'export';
    const FETCH_LIMIT = 1000;

    /**
     * @param \Grido\Grid $grid
     * @param string $label
     */
    public function __construct(\Grido\Grid $grid, $label = NULL)
    {
        $this->grid = $grid;
        $this->label = $label === NULL ? ucfirst($this->grid->getName()) : $label;

        $grid->addComponent($this, self::ID);
    }

    /**
     * @param \Nette\ComponentModel\RecursiveComponentIterator $columns
     * @return string
     */
    protected function generateCsvHeader(\Nette\ComponentModel\RecursiveComponentIterator $columns)
    {
        $head = array();
        foreach ($columns as $column) {
            $head[] = $column->getLabel();
        }

        $resource = fopen('php://temp/maxmemory:' . (5 * 1024 * 1024), 'r+'); // 5MB of memory allocated
        fputcsv($resource, $head);
        rewind($resource);
        $output = stream_get_contents($resource);
        fclose($resource);

        return $output;
    }

    /**
     * @param array $data
     * @param \Nette\ComponentModel\RecursiveComponentIterator $columns
     * @return string
     */
    protected function generateCsv($data, \Nette\ComponentModel\RecursiveComponentIterator $columns)
    {
        $resource = fopen('php://temp/maxmemory:' . (5 * 1024 * 1024), 'r+'); // 5MB of memory allocated

        foreach ($data as $item) {
            $items = array();
            foreach ($columns as $column) {
                $items[] = $column->renderExport($item);
            }

            fputcsv($resource, $items);
        }

        rewind($resource);
        $output = stream_get_contents($resource);
        fclose($resource);

        return $output;
    }

    /**
     * @internal
     */
    public function handleExport()
    {
        $this->grid->onRegistered && $this->grid->onRegistered($this->grid);
        $this->grid->presenter->sendResponse($this);
    }

    /*     * ************************* interface \Nette\Application\IResponse ************************** */

    /**
     * Sends response to output.
     * @param \Nette\Http\IRequest $httpRequest
     * @param \Nette\Http\IResponse $httpResponse
     * @return void
     */
    public function send(\Nette\Http\IRequest $httpRequest, \Nette\Http\IResponse $httpResponse)
    {
        $file = $this->label . '.csv';

        $model = $this->grid->getDataForCsv();
        $numberOfIterations = ceil($model->getCount() / self::FETCH_LIMIT);
        $columns = $this->grid[\Grido\Components\Columns\Column::ID]->getComponents();
        $source = $this->generateCsvHeader($columns);

        for ($i = 0; $i < $numberOfIterations; $i++) {
            $currentOffset = $i * self::FETCH_LIMIT;
            $model->limit($currentOffset, self::FETCH_LIMIT);
            $data = $model->getData();
            $csvData = $this->generateCsv($data, $columns);
            $source .= $csvData;
            unset($data);
        }

        $charset = 'UTF-16LE';
        $source = mb_convert_encoding($source, $charset, 'UTF-8');
        $source = "\xFF\xFE" . $source; //add BOM

        $httpResponse->setHeader('Content-Encoding', $charset);
        $httpResponse->setHeader('Content-Length', strlen($source));
        $httpResponse->setHeader('Content-Type', "text/csv; charset=$charset");
        $httpResponse->setHeader('Content-Disposition', "attachment; filename=\"$file\"; filename*=utf-8''$file");

        echo $source;
    }
}
