<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\Translations;

/**
 * Simple file translator.
 *
 * @package     Grido
 * @subpackage  Translations
 * @author      Petr Bugyík
 */
class FileTranslator extends \Nette\Object implements \Nette\Localization\ITranslator
{
    public $dictionary = array();

    /**
     * @param string $lang
     * @param array $dictionary
     */
    public function __construct($lang = NULL, array $dictionary = array())
    {
        if ($lang) {
            require_once __DIR__ . "/$lang.php";
            $dictionary = $dictionary + $dict;
        }

        $this->dictionary = $dictionary;
    }

    /**
     * Sets language of translation.
     * @param string $lang
     */
    public function setLang($lang)
    {
        require_once __DIR__ . "/$lang.php";
        $this->dictionary = $dict;
    }

    /************************* interface \Nette\Localization\ITranslator **************************/

    /**
     * @param string $message
     * @param int $count plural
     * @return type
     */
    public function translate($message, $count = NULL)
    {
        if (is_array($message)) {
            $tmp = array_shift($message);
            $args = $message;
            $message = $tmp;
        }

        if (!isset($args)) {
            $args = func_get_args();
            array_shift($args);
        }

        if (isset($this->dictionary[$message])) {
            if (is_array($this->dictionary[$message])) {
                if (count($args) > 0) {
                    if (isset($this->dictionary[$message][pluralIndex($args[0])])) {
                        $message = $this->dictionary[$message][pluralIndex($args[0])];
                    }
                }
            } else {
                $message = $this->dictionary[$message];
            }
        }

        if (count($args) > 0) {
            return vsprintf($message, $args);
        }

        return $message;
    }
}
