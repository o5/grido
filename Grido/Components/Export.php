<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\Components;

/**
 * Exporting data to CSV.
 *
 * @package     Grido
 * @subpackage  Components
 * @author      Petr Bugyík
 */
class Export extends Base implements \Nette\Application\IResponse
{
    const ID = 'export';

    /** @var Grido\Grid */
    protected $grid;

    /** @var string */
    protected $name;

    /**
     * @param \Grido\Grid $grid
     * @param string $name
     */
    public function __construct(\Grido\Grid $grid, $name)
    {
        $this->grid = $grid;
        $this->name = $name;

        $grid->addComponent($this, self::ID);
    }

    protected function generateCsv($data, $columns)
    {
        $newLine = "\n";
        $delimiter = "\t";

        $head = array();
        foreach ($columns as $column) {
            $head[] = $column->label;
        }
        $a = FALSE;
        $source = implode($delimiter, $head) . $newLine;
        foreach ($data as $item) {
            if ($a) {
                $source .= $newLine;
            }
            $b = FALSE;
            foreach ($columns as $column) {
                if ($b) {
                    $source .= $delimiter;
                }
                $source .= $column->renderExport($item);
                $b = TRUE;
            }
            $a = TRUE;
        }

        return $source;
    }

    /*************************** interface \Nette\Application\IResponse ***************************/

    /**
     * Sends response to output.
     * @return void
     */
    public function send(\Nette\Http\IRequest $httpRequest, \Nette\Http\IResponse $httpResponse)
    {
        $data = $this->grid->getData(FALSE);
        $columns = $this->grid[\Grido\Components\Columns\Column::ID]->getComponents();
        $source = $this->generateCsv($data, $columns);

        $charset = 'UTF-16LE';
        $source = mb_convert_encoding($source, $charset, 'UTF-8');
        $source = "\xFF\xFE" . $source; //add BOM

        $httpResponse->setHeader('Content-Encoding', $charset);
        $httpResponse->setHeader('Content-Length', strlen($source));
        $httpResponse->setHeader('Content-Type', "text/csv; charset=$charset");
        $httpResponse->setHeader('Content-Disposition', "attachment; filename=\"{$this->name}.csv\"");

        print $source;
    }
}
