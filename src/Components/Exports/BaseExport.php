<?php

namespace Grido\Components\Exports;

use Grido\Components\Component;
use Grido\Grid;
use Nette\Application\IResponse;
use Nette\Utils\Strings;

/**
 * Exporting data.
 *
 * @package     Grido
 * @subpackage  Components
 *
 * @property int $fetchLimit
 * @property-write array $header
 * @property-write callable $customData
 */
abstract class BaseExport extends Component implements IResponse
{

    const ID = 'export';

    /** @var int */
    protected $fetchLimit = 100000;

    /** @var array */
    protected $header = [];

    /** @var callable */
    protected $customData;

    /** @var string */
    private $title;

    /**
     * @param string $label
     */
    public function __construct($label = NULL)
    {
        $this->label = $label;
        $this->monitor('Grido\Grid');
    }

    protected function attached($presenter)
    {
        parent::attached($presenter);
        if ($presenter instanceof Grid) {
            $this->grid = $presenter;
        }
    }

    /**
     * @return void
     */
    abstract protected function printData();

    /**
     * @param \Nette\Http\IResponse $httpResponse
     * @param string $label
     * @return void
     */
    abstract protected function setHttpHeaders(\Nette\Http\IResponse $httpResponse, $label);

    /**
     * @param string $title
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
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
        !empty($this->grid->onRegistered) && $this->grid->onRegistered($this->grid);
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
        $label = $this->label
            ? ucfirst(Strings::webalize($this->label))
            : ucfirst($this->grid->name);

        $this->setHttpHeaders($httpResponse, $label);

        print chr(0xEF) . chr(0xBB) . chr(0xBF); //UTF-8 BOM
        $this->printData();
    }
}
