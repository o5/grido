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
 * Link column.
 *
 * @package     Grido
 * @subpackage  Components\Columns
 * @author      Petr Bugyík
 */
class Link extends Text
{
    /**
     * @param mixed $value
     * @return \Nette\Utils\Html
     */
    protected function formatValue($value)
    {
        return $this->getAnchor($value);
    }

    /**
     * @param string $value
     * @return string
     */
    protected function formatHref($value)
    {
        if (!preg_match('~^\w+://~i', $value)) {
            $value = "http://" . $value;
        }

        return $value;
    }

    /**
     * @param string $value
     * @return string
     */
    protected function formatText($value)
    {
        return preg_replace('~^https?://~i', '', $value);
    }

    /**
     * @param mixed $value
     * @return \Nette\Utils\Html
     */
    protected function getAnchor($value)
    {
        $truncate = $this->truncate;
        $this->truncate = NULL;

        $value = parent::formatValue($value);
        $href = $this->formatHref($value);
        $text = $this->formatText($value);

        $anchor = \Nette\Utils\Html::el('a')
            ->setHref($href)
            ->setText($text)
            ->setTarget('_blank')
            ->setRel('noreferrer');

        if ($truncate) {
            $anchor->setText($truncate($text))
                ->setTitle($value);
        }

        return $anchor;
    }
}
