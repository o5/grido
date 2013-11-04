<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
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
    /** @var array */
    protected $translations = array();

    /**
     * @param string $lang
     * @param array $translations
     */
    public function __construct($lang = NULL, array $translations = array())
    {
        if ($lang) {
            $translations = $translations + $this->getTranslationsFromFile($lang);
        }

        $this->translations = $translations;
    }

    /**
     * Sets language of translation.
     * @param string $lang
     */
    public function setLang($lang)
    {
        $this->translations = $this->getTranslationsFromFile($lang);
    }

    /**
     * @param string $lang
     * @throws \Exception
     * @return array
     */
    protected function getTranslationsFromFile($lang)
    {
        if (!$translations = @include(__DIR__ . "/$lang.php")) {
            throw new \Exception("Translations for language '$lang' not found.");
        }

        return $translations;
    }

    /************************* interface \Nette\Localization\ITranslator **************************/

    /**
     * @param string $message
     * @param int $count plural
     * @return string
     */
    public function translate($message, $count = NULL)
    {
        return isset($this->translations[$message])
            ? $this->translations[$message]
            : $message;
    }
}
