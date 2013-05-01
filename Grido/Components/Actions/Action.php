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
 * Action on one row.
 *
 * @package     Grido
 * @subpackage  Components\Actions
 * @author      Petr Bugyík
 *
 * @property-write \Nette\Utils\Html $elementPrototype
 * @property-write array $customRender
 * @property-write array $disable
 * @property string $primaryKey
 */
abstract class Action extends \Grido\Components\Base
{
    const ID = 'actions';

    const TYPE_HREF = 'Grido\Components\Actions\Href';

    /** @var callback for custom rendering */
    protected $customRender;

    /** @var callback for disabling */
    protected $disable;

    /** @var \Nette\Utils\Html <a> html tag */
    protected $elementPrototype;

    /** @var string first param for method $presenter->link() */
    protected $destination;

    /** @var array second param for method $presenter->link() */
    protected $arguments = array();

    /** @var string - name of primary key f.e.: link->('Article:edit', array($primaryKey => 1)) */
    protected $primaryKey;

    /**
     * @param \Grido\Grid $grid
     * @param string $name
     * @param string $label
     * @param string $destination - first param for method $presenter->link()
     * @param array $args - second param for method $presenter->link()
     */
    public function __construct($grid, $name, $label, $destination = NULL, array $args = NULL)
    {
        $this->addComponentToGrid($grid, $name);

        $this->type = get_class($this);
        $this->label = $label;
        $this->destination = $destination;
        $this->arguments = $args;
    }

    /**
     * Sets callback for custom rendering.
     * @param callback
     * @return Action
     */
    public function setCustomRender($callback)
    {
        $this->customRender = $callback;
        return $this;
    }

    /**
     * Sets primary key.
     * @param string $primaryKey
     * @return Action
     */
    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
        return $this;
    }

    /**
     * Sets html element.
     * @param \Nette\Utils\Html $elementPrototype
     * @return Action
     */
    public function setElementPrototype(\Nette\Utils\Html $elementPrototype)
    {
        $this->elementPrototype = $elementPrototype;
        return $this;
    }

    /**
     * Sets callback for disable.
     * Callback should return TRUE if the action is not allowed for current item.
     * @param callback
     * @return Action
     */
    public function setDisable($callback)
    {
        $this->disable = $callback;
        return $this;
    }

    /**********************************************************************************************/

    /**
     * @internal
     * @return string
     */
    public function getPrimaryKey()
    {
        if ($this->primaryKey === NULL) {
            $this->primaryKey = $this->grid->primaryKey;
        }

        return $this->primaryKey;
    }

    /**
     * @internal
     * @return string
     */
    protected function getDestination()
    {
        if ($this->destination === NULL) {
            $this->destination = $this->name;
        }

        return $this->destination;
    }
}
