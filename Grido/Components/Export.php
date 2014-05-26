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

    const NEW_LINE = "\n";
    const DELIMITER = "\t";

    /**
     * @param Grido\Grid $grid
     * @param string $label
     */
    public function __construct(\Grido\Grid $grid, $label = NULL)
    {
        $this->grid = $grid;
        $this->label = $label === NULL
            ? ucfirst($this->grid->getName())
            : $label;

        $grid->addComponent($this, self::ID);
    }

    /**
     * @param array $data
     * @param \Nette\ComponentModel\RecursiveComponentIterator $columns
     * @return string
     */
    protected function generateCsv($data, $columns)
    {
        $head = array();
        foreach ($columns as $column) {
            $head[] = $column->getLabel();
        }

        $addNewLine = FALSE;
        $source = implode(static::DELIMITER, $head) . static::NEW_LINE;
        foreach ($data as $item) {
            $source .= $addNewLine ? static::NEW_LINE : NULL;

            $addDelimiter = FALSE;
            foreach ($columns as $column) {
                $source .= $addDelimiter ? static::DELIMITER : NULL;
                $source .= $column->renderExport($item);

                $addDelimiter = TRUE;
            }

            $addNewLine = TRUE;
        }

        return $source;
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
     * @return void
     */
    public function send(\Nette\Http\IRequest $httpRequest, \Nette\Http\IResponse $httpResponse)
    {
        $file = $this->label . '.csv';
        $data = $this->grid->getData(FALSE);
        $columns = $this->grid[\Grido\Components\Columns\Column::ID]->getComponents();
        $source = $this->generateCsv($data, $columns);

        $charset = 'UTF-16LE';
        $source = mb_convert_encoding($source, $charset, 'UTF-8');
        $source = "\xFF\xFE" . $source; //add BOM

        $httpResponse->setHeader('Content-Encoding', $charset);
        $httpResponse->setHeader('Content-Length', strlen($source));
        $httpResponse->setHeader('Content-Type', "text/csv; charset=$charset");
        $httpResponse->setHeader('Content-Disposition', "attachment; filename=\"$file\"; filename*=utf-8''$file");

        print $source;
    }
}
