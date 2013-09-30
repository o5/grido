<?php

/**
 * Test: Filter.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

namespace Grido\Tests;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Helper.inc.php';

use Tester\Assert,
    Grido\Grid;

class FilterText extends \Tester\TestCase
{
    function testSetSuggestion()
    {
        Helper::grid(function(Grid $grid) {
            $grid->setModel(array(
                array('name' => 'AAtest'),
                array('name' => 'AAxxx'),
                array('name' => 'BBtest'),
                array('name' => 'BBxxx'),
            ));
            $grid->addColumnText('name', 'Name');
            $filter = $grid->addFilterText('name', 'Name')->setSuggestion();

            Assert::same('off', $filter->control->controlPrototype->attrs['autocomplete']);
            Assert::same(array('text', 'suggest'), $filter->control->controlPrototype->class);
        })->run();

        ob_start();
            Helper::$grid->render();
        ob_clean();

        $prototype = Helper::$grid->getFilter('name')->control->controlPrototype;
        Assert::same('-query-', $prototype->data['grido-suggest-replacement']);

        $url = '/index.php?grid-filters-name-query=-query-&action=default&do=grid-filters-name-suggest&presenter=Test';
        Assert::same($url, $prototype->data['grido-suggest-handler']);

        Helper::$presenter->forceAjaxMode = TRUE;
        Helper::request();

        ob_start();
            Helper::$grid->getFilter('name')->handleSuggest('aa');
        $output = ob_get_clean();
        Assert::same('["AAtest","AAxxx"]', $output);

        ob_start();
            Helper::$grid->getFilter('name')->handleSuggest('xx');
        $output = ob_get_clean();
        Assert::same('["AAxxx","BBxxx"]', $output);

        ob_start();
            Helper::$grid->getFilter('name')->handleSuggest('###');
        $output = ob_get_clean();
        Assert::same('[]', $output);
    }

    function testFormControl()
    {
        $grid = new Grid;
        $filter = $grid->addFilterText('text', 'Text');
        Assert::type('Nette\Forms\Controls\TextInput', $filter->control);
    }

    function testMakeFilter() //__makeFilter()
    {
        $grid = new Grid;
        $filter = $grid->addFilterText('text', 'Text');
        Assert::same(array(' ([text] LIKE %s )', '%value%'), $filter->__makeFilter('value'));
    }
}

run(__FILE__);
