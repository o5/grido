<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\Components\Columns;

/**
 * Boolean column.
 *
 * @package     Grido
 * @subpackage  Components\Columns
 * @author      Pavel Kryštůfek (http://www.krystufkovi.cz)
 */
class Boolean extends Text
{
	/**
	 * @param $value
	 * @return \Nette\Utils\Html
	 */
	protected function formatValue($value)
	{
		if ($value == 0) {
			$a = \Nette\Utils\Html::el('i')->class("icon-remove");
		} else {
			$a = \Nette\Utils\Html::el('i')->class("icon-ok");
		}

		return $a;
	}
}
