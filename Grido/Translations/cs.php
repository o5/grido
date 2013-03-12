<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

$dict = array(
    'You can use <, <=, >, >=, <>. e.g. ">= %d"' => 'Můžete použít <, <=, >, >=, <>. Např.: ">= %d"',
    'Select some row' => 'Vyberte řádek',
    'Invert' => 'Obrátit výběr',
    'Items %d - %d of %d' => 'Položky %d - %d z %d',
    'Previous' => 'Předchozí',
    'Next' => 'Další',
    'Actions' => 'Akce',
    'Search' => 'Vyhledat',
    'Reset' => 'Resetovat',
    'Items per page' => 'Položek na stránku',
    'Selected...' => 'Vybrané...',
    'Enter page:' => 'Vložte stranu:',
    'No results.' => 'Žádné výsledky.',
    'Export all items' => 'Exportovat všechny položky',
);

function pluralIndex($number)
{
    $number = (int) $number;
    if ($number == 1) {
        return 0;
    } else if ($number >= 2 && $number <= 4) {
        return 1;
    } else {
        return 2;
    }
}
