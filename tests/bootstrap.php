<?php

/**
 * Test initialization and helpers.
 *
 * @author     David Grudl
 * @author     Petr Bugyík
 * @package    Nette\Test
 */

if (@!include __DIR__ . '/../vendor/autoload.php') {
    echo 'Install Nette Tester using `composer update --dev`';
    exit(1);
}

// configure environment
Tester\Environment::setup();
class_alias('\Tester\Assert', 'Assert');
class_alias('\Grido\Grid', 'Grid');
date_default_timezone_set('Europe/Prague');


// create temporary directory
define('TEMP_DIR', __DIR__ . '/tmp/' . getmypid());
@mkdir(dirname(TEMP_DIR)); // @ - directory may already exist
Tester\Helpers::purge(TEMP_DIR);


if (extension_loaded('xdebug')) {
    xdebug_disable();
    Tester\CodeCoverage\Collector::start(__DIR__ . '/coverage.dat');
}

function id($val)
{
    return $val;
}

function before(\Closure $function = NULL)
{
    static $val;
    if (!func_num_args()) {
        return ($val ? $val() : NULL);
    }
    $val = $function;
}

function test(\Closure $function)
{
    before();
    $function();
}

function mock()
{
    return call_user_func_array('Mockery::mock', func_get_args());
}
