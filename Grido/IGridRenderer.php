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
 * Defines method that must implement form renderer.
 *
 * @package     Grido
 * @author      Josef Kříž <pepakriz@gmail.com>
 */
interface IGridRenderer
{

    /**
     * Provides complete form rendering.
     * @return string
     */
    public function render(Grid $grido);

}
