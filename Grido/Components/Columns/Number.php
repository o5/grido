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

use Grido\Components\Filters\Filter;

/**
 * Number column.
 *
 * @package     Grido
 * @subpackage  Components\Columns
 * @author      Petr Bugyík
 */
class Number extends Column
{
     /** @var int */
     protected $decimals = 0;
     /** @var string */
     protected $dec_point = '.';
     /** @var string */
     protected $thousands_sep = ',';
             
    public function setNumberFormat($decimals = 0, $dec_point = '.' , $thousands_sep = ',')
    {
        $this->decimals = $decimals;
        $this->dec_point = $dec_point;
        $this->thousands_sep = $thousands_sep;
        
        return $this;
    }

    public function setFilter($type = Filter::TYPE_NUMBER, $optional = NULL)
    {
        return $this->grid->addFilter($this->name, $this->label, $type, $optional);
    }    
    
    /**
     * @param $value
     * @return string
     */
    protected function formatValue($value)
    {
        if (!isset($value))
            return null;
        return is_numeric($value) ? number_format($value, $this->decimals, $this->dec_point, $this->thousands_sep) : $value;
    }

}
