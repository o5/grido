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
 * Paginating grid.
 *
 * @package     Grido
 * @author      Petr Bugyík
 *
 * @property-read int $page
 * @property-read array $steps
 * @property-read int $countEnd
 * @property-read int $countBegin
 * @property-write Grid $grid
 */
class Paginator extends \Nette\Utils\Paginator
{
    /** @var int */
    protected $page;

    /** @var array */
    protected $steps = array();

    /** @var int */
    protected $countBegin;

    /** @var int */
    protected $countEnd;

    /** @var Grid */
    protected $grid;

    /**
     * @param Grid $grid
     * @return Paginator
     */
    public function setGrid(Grid $grid)
    {
        $this->grid = $grid;
        return $this;
    }

    /**********************************************************************************************/

    /**
     * @return int
     */
    public function getPage()
    {
        if ($this->page === NULL) {
            $this->page = parent::getPage();
        }

        return $this->page;
    }

    /**
     * @return array
     */
    public function getSteps()
    {
        if (!$this->steps) {
            $arr = range(
                max($this->firstPage, $this->getPage() - 3),
                min($this->lastPage, $this->getPage() + 3)
            );

            $count = 4;
            $quotient = ($this->pageCount - 1) / $count;

            for ($i = 0; $i <= $count; $i++) {
                $arr[] = round($quotient * $i) + $this->firstPage;
            }

            sort($arr);
            $this->steps = array_values(array_unique($arr));
        }

        return $this->steps;
    }

    /**
     * @return int
     */
    public function getCountBegin()
    {
        if ($this->countBegin === NULL) {
            $this->countBegin = $this->grid->getCount() > 0 ? $this->getOffset() + 1 : 0;
        }

        return $this->countBegin;
    }

    /**
     * @return int
     */
    public function getCountEnd()
    {
        if ($this->countEnd === NULL) {
            $this->countEnd = $this->grid->getCount() > 0
                ? $this->getPage() * $this->grid->getPerPage()
                : 0;
        }

        return $this->countEnd;
    }
}
