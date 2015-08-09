<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\Components\Columns;

/**
 * Check column.
 *
 * @package     Grido
 * @subpackage  Components\Columns
 * @author      Tomáš Pilař
 */
class Check extends Editable
{
	/**
	 * @param mixed $value
	 * @return mixed
	 */
	protected function formatValue($value)
	{
		$value = parent::formatValue($value);

		return $value
			? $this->translate('Grido.Columns.Check.Positive')
			: $this->translate('Grido.Columns.Check.Negative');
	}

	/**
	 * @param mixed $row
	 * @return string
	 * @internal
	 */
	public function renderExport($row)
	{
		if (is_callable($this->customRenderExport)) {
			return callback($this->customRenderExport)->invokeArgs(array($row));
		}

		$value = $this->getValue($row);
		return $this->formatValue($value);
	}
}
