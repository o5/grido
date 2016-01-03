<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido;

use Nette\Object;

/**
 * Customization.
 *
 * @package     Grido
 * @author      Petr Bugyík
 *
 * @property string|array $buttonClass
 * @property string|array $iconClass
 */
class Customization extends Object
{

    /** @var string|array */
    protected $buttonClass;

    /** @var string|array */
    protected $iconClass;

    /**
     * @param string|array $class
     * @return \Grido\Customization
     */
    public function setButtonClass($class)
    {
        $this->buttonClass = $class;
        return $this;
    }

    /**
     * @param string|array $class
     * @return \Grido\Customization
     */
    public function setIconClass($class)
    {
        $this->iconClass = $class;
        return $this;
    }

    /**
     * @return string
     */
    public function getButtonClass()
    {
        return is_array($this->buttonClass)
            ? implode(' ', $this->buttonClass)
            : $this->buttonClass;
    }

    /**
     * @param string $icon
     * @return string
     */
    public function getIconClass($icon = NULL)
    {
        if ($icon === NULL) {
            $class = $this->iconClass;
        } else {
            $this->iconClass = (array) $this->iconClass;
            $classes = [];
            foreach ($this->iconClass as $fontClass) {
                $classes[] = "{$fontClass} {$fontClass}-{$icon}";
            }
            $class = implode(' ', $classes);
        }

        return $class;
    }

    /**
     * @return array
     */
    public function getTemplateFiles()
    {
        $list = [];
        foreach (new \DirectoryIterator(__DIR__ . '/templates') as $file) {
            if ($file->isFile()) {
                $list[$file->getBasename('.latte')] = realpath($file->getPathname());
            }
        }

        return $list;
    }
}
