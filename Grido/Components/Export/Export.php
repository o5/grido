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
 * Data exporting.
 *
 * @package     Grido
 * @subpackage  Export
 * @author      Petr Bugyík
 *
 * @property-read $types
 */
class Export extends Base implements IExport
{
    const ID = 'export';

    const CSV_DELIMITER = ';';
    const CSV_NEW_LINE = "\n";

    /** @var Grid */
    protected $grid;

    /** @var string */
    protected $name;

    /** @var string */
    protected $type;

    /** @var array */
    protected $types = array(
        'xls' => 'Export to XLS',
        'csv' => 'Export to CSV'
    );

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

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**********************************************************************************************/

    /**
     * @internal
     * @param string $type
     * @return bool
     */
    public function hasType($type)
    {
        $this->type = $type;
        return isset($this->types[$type]);
    }

    protected function getSource()
    {
        $data = $this->grid->getData(FALSE);
        $columns = $this->grid[\Grido\Columns\Column::ID]->getComponents();

        $method = "getSource{$this->type}";
        list ($source, $contentType) = $this->$method($data, $columns);

        $charset = 'UTF-16LE';
        $source = mb_convert_encoding($source, $charset, 'UTF-8');
        $source = "\xFF\xFE" . $source; //add BOM

        $response = $this->grid->presenter->context->getByType('Nette\Http\IResponse', 'UTF-8');
        $response->setHeader('Content-Encoding', $charset);
        $response->setHeader('Content-Length', strlen($source));
        $response->setHeader('Content-Type', "$contentType;  charset=$charset;");
        $response->setHeader('Content-Disposition', "attachment; filename='{$this->name}.{$this->type}';");

        return $source;
    }

    protected function getSourceXls($data, $columns)
    {
        $template = new \Nette\Templating\FileTemplate(__DIR__ . '/xls.latte');
        $template->registerFilter(new \Nette\Latte\Engine);
        $template->columns = $columns;
        $template->data = $data;

        return array((string) $template, 'application/vnd.ms-excel');
    }

    protected function getSourceCsv($data, $columns)
    {
        $source = '';
        $head = array();
        foreach ($columns as $column) {
            $head[] = $column->label;
        }
        $a = FALSE;
        $source = implode(self::CSV_DELIMITER, $head) . self::CSV_NEW_LINE;
        foreach ($data as $item) {
            if ($a) {
                $source .= self::CSV_NEW_LINE;
            }
            $b = FALSE;
            foreach ($columns as $column) {
                if ($b) {
                    $source .= self::CSV_DELIMITER;
                }
                $source .= $item[$column->column];
                $b = TRUE;
            }
            $a = TRUE;
        }

        return array($source, 'text/csv');
    }

    /**
     * Sends response to output.
     * @return void
     */
    public function send(\Nette\Http\IRequest $httpRequest, \Nette\Http\IResponse $httpResponse)
    {
        $source = $this->getSource();
        if ($source instanceof Nette\Templating\ITemplate) {
            $source->render();
        } else {
            print $source;
        }
    }
}
