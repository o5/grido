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
 * Email column.
 *
 * @package     Grido
 * @subpackage  Components\Columns
 * @author      Petr Bugyík
 */
class Email extends Text
{
    /**
     * @param $value
     * @return \Nette\Utils\Html
     */
    protected function formatValue($value)
    {
        $truncate = $this->truncate;
        $this->truncate = NULL;
        $value = parent::formatValue($value);

        $anchor = \Nette\Utils\Html::el('a')->href("mailto:$value")->setText($value);

        if ($truncate) {
            $anchor->setText($truncate($value))
                ->setTitle($value);
        }

        return $anchor;
    }
}
