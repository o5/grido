<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido;

/**
 * Exporting data to CSV.
 *
 * @package     Grido
 * @subpackage  Export
 * @author      Petr Bugyík
 */
class Export extends Base implements \Nette\Application\IResponse
{
    const ID = 'export';

    /** @var Grid */
    protected $grid;

    /** @var string */
    protected $name;

    /**
     * @param Grid $grid
     * @param string $name
     */
    public function __construct(Grid $grid, $name)
    {
        $this->grid = $grid;
        $this->name = $name;

        $grid->addComponent($this, self::ID);
    }

    /**********************************************************************************************/

    protected function getResponse()
    {
        $data = $this->grid->getData(FALSE);
        $columns = $this->grid[\Grido\Columns\Column::ID]->getComponents();
        $source = $this->generateCsv($data, $columns);

        $charset = 'UTF-16LE';
        $source = mb_convert_encoding($source, $charset, 'UTF-8');
        $source = "\xFF\xFE" . $source; //add BOM

        $response = $this->grid->presenter->context->getByType('Nette\Http\IResponse', 'UTF-8');
        $response->setHeader('Content-Encoding', $charset);
        $response->setHeader('Content-Length', strlen($source));
        $response->setHeader('Content-Type', "text/csv; charset=$charset");
        $response->setHeader('Content-Disposition', "attachment; filename=\"{$this->name}.csv\"");

        return $source;
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

    /**
     * Sends response to output.
     * @return void
     */
    public function send(\Nette\Http\IRequest $httpRequest, \Nette\Http\IResponse $httpResponse)
    {
        print $this->getResponse();
        $this->grid->presenter->terminate();
    }
}
