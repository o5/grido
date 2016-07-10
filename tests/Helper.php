<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\Tests;

use Tester\Assert;

/**
 * Test helper.
 *
 * @author     Petr Bugyík
 * @package    Grido\Tests
 */
class Helper
{
    const GRID_NAME = 'grid';

    /** @var \Grido\Grid */
    public static $grid;

    /** @var TestPresenter */
    public static $presenter;

    /**
     * @param \Closure $definition of grid; function(Grid $grid, TestPresenter $presenter) { };
     */
    public static function grid(\Closure $definition)
    {
        $self = new self;

        if (self::$presenter === NULL) {
            self::$presenter = $self->createPresenter();
        }

        self::$presenter->onStartUp = [];
        self::$presenter->onStartUp[] = function(TestPresenter $presenter) use ($definition) {
            if (isset($presenter[Helper::GRID_NAME])) {
                unset($presenter[Helper::GRID_NAME]);
            }

            $definition(new \Grido\Grid($presenter, Helper::GRID_NAME), $presenter);
        };

        return $self;
    }

    /**
     * @param array $params
     * @param string $method
     * @return \Nette\Application\IResponse
     */
    public static function request(array $params = [], $method = \Nette\Http\Request::GET)
    {
        $request = new \Nette\Application\Request('Test', $method, $params);
        $response = self::$presenter->run($request);

        self::$grid = self::$presenter[self::GRID_NAME];

        return $response;
    }

    /**
     * @param array $params
     * @param string $method
     * @return \Nette\Application\IResponse
     */
    public function run(array $params = [], $method = \Nette\Http\Request::GET)
    {
        return self::request($params, $method);
    }

    public static function assertTypeError($function)
    {
        if (PHP_VERSION_ID < 70000) {
            Assert::error($function, E_RECOVERABLE_ERROR);
        } else {
            Assert::exception($function, '\TypeError');
        }
    }

    /**
     * @return \TestPresenter
     */
    private function createPresenter()
    {
        $url = new \Nette\Http\UrlScript('http://localhost/');
        $url->setScriptPath('/');

        $configurator = new \Nette\Configurator;
        $configurator->addConfig(__DIR__ . '/config.neon');
        \Kdyby\Events\DI\EventsExtension::register($configurator);
        \Kdyby\Annotations\DI\AnnotationsExtension::register($configurator);
        \Kdyby\Doctrine\DI\OrmExtension::register($configurator);

        $container = $configurator
            ->setTempDirectory(TEMP_DIR)
            ->createContainer();
        $container->removeService('httpRequest');
        $container->addService('httpRequest', new \Nette\Http\Request($url));

        $router = $container->getByType(\Nette\Application\IRouter::class);
        $router[] = new \Nette\Application\Routers\Route('<presenter>/<action>[/<id>]', 'Dashboard:default');

        $presenter = new TestPresenter($container);
        $container->callInjects($presenter);
        $presenter->invalidLinkMode = $presenter::INVALID_LINK_WARNING;
        $presenter->autoCanonicalize = FALSE;

        return $presenter;
    }
}

class TestPresenter extends \Nette\Application\UI\Presenter
{
    /** @var array */
    public $onStartUp;

    /** @var bool */
    public $forceAjaxMode = FALSE;

    public function startup()
    {
        parent::startup();

        $this->onStartUp($this);
    }

    public function sendTemplate()
    {
        //parent::sendTemplate(); intentionally
    }

    public function sendResponse(\Nette\Application\IResponse $response)
    {
        if($response instanceof \Nette\Application\Responses\JsonResponse){
            $response->send($this->getHttpRequest(), $this->getHttpResponse());
        } else {
            parent::sendResponse($response);
        }
    }

    public function isAjax()
    {
        return $this->forceAjaxMode === TRUE
            ? TRUE
            : parent::isAjax();
    }

    public function terminate()
    {
        if ($this->forceAjaxMode === FALSE) {
            parent::terminate();
        }
    }
}
