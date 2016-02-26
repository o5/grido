<?php

namespace Grido\Components\Exports;

use Grido\Components\Columns\Column;
use Nette\Http\IResponse;

class CsvExport extends BaseExport
{

    /** @deprecated */
    const CSV_ID = 'csv';

    /**
     * @return void
     */
    protected function printData()
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

        $header = [];
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
                ? call_user_func_array($this->customData, [$datasource])
                : $datasource->getData();

            foreach ($data as $items) {
                $row = [];

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
     * @param IResponse $httpResponse
     * @param string $label
     */
    protected function setHttpHeaders(IResponse $httpResponse, $label)
    {
        $encoding = 'utf-8';
        $httpResponse->setHeader('Content-Encoding', $encoding);
        $httpResponse->setHeader('Content-Type', "text/csv; charset=$encoding");
        $httpResponse->setHeader('Content-Disposition', "attachment; filename=\"$label.csv\"");
    }
}
