<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\Components\Filters;

/**
 * Filter with custom form control.
 *
 * @package     Grido
 * @subpackage  Components\Filters
 * @author      Petr Bugyík
 *
 * @property-read \Nette\Forms\IControl $formControl
 */
class Custom extends Filter
{
    /** @var \Nette\Forms\IControl */
    protected $formControl;

    /**
     * @param \Grido\Grid $grid
     * @param string $name
     * @param string $label
     * @param \Nette\Forms\IControl $formControl
     */
    public function __construct($grid, $name, $label, \Nette\Forms\IControl $formControl)
    {
        $this->formControl = $formControl;

        parent::__construct($grid, $name, $label);
    }

    /**
     * @return \Nette\Forms\IControl
     * @internal
     */
    public function getFormControl()
    {
        return $this->formControl;
    }
}
