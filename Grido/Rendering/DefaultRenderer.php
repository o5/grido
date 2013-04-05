<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\Rendering;

use Grido\Grid;
use Grido\IGridRenderer;
use Nette;
use Nette\Object;
use Nette\Templating\FileTemplate;
use Nette\Templating\Template;

/**
 * Converts a Grid into the HTML output.
 *
 * @package     Grido
 * @subpackage  Rendering
 * @author      Josef Kříž <pepakriz@gmail.com>
 */
class DefaultRenderer extends Object implements IGridRenderer
{

    /** @var Template */
    protected $template;

    /**
     * Provides complete form rendering.
     * @return string
     */
    public function render(Grid $grid)
    {
        if ($this->template === NULL) {
            if ($presenter = $grid->lookup('Nette\Application\UI\Presenter', FALSE)) {
                /** @var \Nette\Application\UI\Presenter $presenter */
                $this->template = clone $presenter->getTemplate();

            } else {
                $this->template = new FileTemplate();
                $this->template->registerFilter(new Nette\Latte\Engine());
            }
        }

        $this->template->setFile(__DIR__ . '/@defaultRenderer.latte');
        $this->template->setParameters(array(
            '_form' => $grid['form'],
            'form' => $grid['form'],
            'renderer' => $this,
            'control' => $grid,
            '_control' => $grid,
        ));
        $this->template->registerHelper('translate', callback($grid->getTranslator(), 'translate'));

        $this->template->paginator = $grid->paginator;
        $this->template->data = $grid->getData();
        $this->template->render();
    }

}
