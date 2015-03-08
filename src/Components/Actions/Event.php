<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\Components\Actions;

/**
 * Event action.
 *
 * @package     Grido
 * @subpackage  Components\Actions
 * @author      Josef Kříž <pepakriz@gmail.com>
 * @author      Petr Bugyík
 *
 * @method void onClick(int $id, Event $event)
 */
class Event extends Action
{
    /** @var callback callback */
    public $onClick = array();

    /**
     * @param \Grido\Grid $grid
     * @param string $name
     * @param string $label
     * @param callback $onClick
     */
    public function __construct($grid, $name, $label, $onClick = NULL)
    {
        parent::__construct($grid, $name, $label);

        if ($onClick !== NULL) {
            $this->onClick[] = $onClick;
        }
    }

    /**
     * Sets on-click handler.
     * @param callback $onClick
     * @return \Grido\Components\Actions\Event
     */
    public function setOnClick($onClick)
    {
        $this->onClick = $onClick;
        return $this;
    }

    /**********************************************************************************************/

    /**
     * @param mixed $row
     * @return \Nette\Utils\Html
     * @internal
     */
    public function getElement($row)
    {
        $element = parent::getElement($row);

        $primaryValue = $this->grid->getProperty($row, $this->getPrimaryKey());
        $element->href($this->link('click!', $primaryValue));

        return $element;
    }

    /**********************************************************************************************/

    /**
     * @param $id
     * @internal
     */
    public function handleClick($id)
    {
        $this->onClick($id, $this);
    }
}
