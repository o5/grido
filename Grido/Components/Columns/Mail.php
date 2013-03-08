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
 * Mail column.
 *
 * @package     Grido
 * @subpackage  Components\Columns
 * @author      Petr Bugyík
 */
class Mail extends Text
{
    /**
     * @param $value
     * @return \Nette\Utils\Html
     */
    protected function formatValue($value)
    {
        $a = \Nette\Utils\Html::el('a')->href("mailto:$value")->setText($value);

        if ($this->truncate) {
            $truncate = $this->truncate;
            $a->setText($truncate($value))
                ->setTitle($value);
        }

        return $a;
    }
}
