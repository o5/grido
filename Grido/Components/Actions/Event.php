<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\Components\Actions;

/**
 * Event action.
 *
 * @package     Grido
 * @subpackage  Components\Actions
 * @author      Josef Kříž <pepakriz@gmail.com>
 */
class Event extends Action
{

    /** @var array */
    public $onClick;

    /** @var array */
    public $onSuccess;

    /**
     * @param $item
     * @return \Nette\Utils\Html|void
     */
    public function getElement($item)
    {
        $el = parent::getElement($item);
        $el->href($this->link('click!', $this->grid->propertyAccessor->getProperty($item, $this->primaryKey)));
        return $el;
    }


    /**
     * @param $id
     */
    public function handleClick($id)
    {
        $this->onClick($id, $this);
        $this->onSuccess($id, $this);
    }
}
