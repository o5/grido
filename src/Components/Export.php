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
	
	/** @var string */
	private $cvsDelimiter = ',';
	
	/** @var string */
	private $csvEnclosure = '"';

    /**
     * @param \Grido\Grid $grid
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

	public function setCsv($delimiter = ',', $enclosure = '"')
	{
		$this->cvsDelimiter = $delimiter;
		$this->csvEnclosure = $enclosure;
		return $this;
	}
	
    /**
     * @return string
     */
    protected function generateSource()
    {
        $limit = 100;
        $datasource = $this->grid->getData(FALSE, FALSE, FALSE);
        $iterations = ceil($datasource->getCount() / $limit);

        $columns = $this->grid[Columns\Column::ID]->getComponents();
        $resource = fopen('php://temp', 'r+');

        //generate header
        $header = array();
        foreach ($columns as $column) {
			if ($column->isExportable()) {
				$header[] = $column->getLabel();
			}
        }

        fputcsv($resource, $header);

        for ($i = 0; $i < $iterations; ++$i) {
            $datasource->limit($i * $limit, $limit);
            $data = $datasource->getData();

            foreach ($data as $item) {
                $row = array();

                foreach ($columns as $column) {
					if ($column->isExportable()) {
						$row[] = $column->renderExport($item);
					}
				}

                fputcsv($resource, $row, $this->csvDelimiter, $this->csvEnclosure);
                unset($row);
            }

            unset($data);
        }

        rewind($resource);
        $source = stream_get_contents($resource);
        fclose($resource);

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
     * @param \Nette\Http\IRequest $httpRequest
     * @param \Nette\Http\IResponse $httpResponse
     * @return void
     */
    public function send(\Nette\Http\IRequest $httpRequest, \Nette\Http\IResponse $httpResponse)
    {
        $encoding = 'UTF-16LE';
        $file = $this->label . '.csv';

        $source = $this->generateSource();
        $source = mb_convert_encoding($source, $encoding, 'UTF-8');
        $source = "\xFF\xFE" . $source; //add BOM

        $httpResponse->setHeader('Content-Encoding', $encoding);
        $httpResponse->setHeader('Content-Length', mb_strlen($source, 'UTF-8'));
        $httpResponse->setHeader('Content-Type', "text/csv; charset=$encoding");
        $httpResponse->setHeader('Content-Disposition', "attachment; filename=\"$file\"; filename*=utf-8''$file");

        print $source;
    }
}
