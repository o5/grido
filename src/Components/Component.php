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
 * Base of grid components.
 *
 * @package     Grido
 * @subpackage  Components
 * @author      Petr Bugyík
 *
 * @property-read string $label
 * @property-read string $type
 * @property-read \Grido\Grid $grid
 * @property-read \Nette\Application\UI\Form $form
 */
abstract class Component extends \Nette\Application\UI\PresenterComponent
{
    /** @var string */
    protected $label;

    /** @var string */
    protected $type;

    /** @var \Grido\Grid */
    protected $grid;

    /** @var \Nette\Application\UI\Form */
    protected $form;

    /**
     * @return \Grido\Grid
     */
    public function getGrid()
    {
        return $this->grid;
    }

    /**
     * @return \Nette\Application\UI\Form
     */
    public function getForm()
    {
        if ($this->form === NULL) {
            $this->form = $this->grid->getComponent('form');
        }

        return $this->form;
    }

    /**
     * @return string
     * @internal
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return string
     * @internal
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param \Grido\Grid $grid
     * @param string $name
     * @return \Nette\ComponentModel\Container
     */
    protected function addComponentToGrid($grid, $name)
    {
        $this->grid = $grid;

        //check container exist
        $container = $this->grid->getComponent($this::ID, FALSE);
        if (!$container) {
            $this->grid->addComponent(new \Nette\ComponentModel\Container, $this::ID);
            $container = $this->grid->getComponent($this::ID);
        }

        return $container->addComponent($this, $name);
    }

    /**
     * @param  string $message
     * @return string
     */
    protected function translate($message)
    {
        return call_user_func_array([$this->grid->getTranslator(), "translate"], func_get_args());
    }
}
