<?php

/**
 * Test initialization and helpers.
 *
 * @author     David Grudl
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */

if (@!include __DIR__ . '/../../vendor/autoload.php') {
    echo 'Install Nette Tester using `composer update --dev`';
    exit(1);
}

// configure environment
Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

Nette\Diagnostics\Debugger::$maxDepth = 5;

// create temporary directory
define('TEMP_DIR', __DIR__ . '/tmp/' . getmypid());
@mkdir(dirname(TEMP_DIR)); // @ - directory may already exist
Tester\Helpers::purge(TEMP_DIR);

\Nette\Diagnostics\Debugger::$maxDepth = 5;


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

function run($path, $name = NULL)
{
    $code = file_get_contents($path);
    $tokens = token_get_all($code);
    $count = count($tokens);
    for ($i = 2; $i < $count; $i++) {
        if ($tokens[$i - 2][0] == T_CLASS && $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING) {
            $test = new $tokens[$i][1];
            if ($name) {
                $test->runTest($name);
            } else {
                $test->run();
            }
        }
    }
}