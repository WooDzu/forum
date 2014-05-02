<?php

/*
    +------------------------------------------------------------------------+
    | Phosphorum                                                             |
    +------------------------------------------------------------------------+
    | Copyright (c) 2013-2014 Phalcon Team and contributors                  |
    +------------------------------------------------------------------------+
    | This source file is subject to the New BSD License that is bundled     |
    | with this package in the file docs/LICENSE.txt.                        |
    |                                                                        |
    | If you did not receive a copy of the license and are unable to         |
    | obtain it through the world-wide-web, please send an email             |
    | to license@phalconphp.com so we can send you a copy immediately.       |
    +------------------------------------------------------------------------+
    | @category  PhalconEye                                                  |
    | @package   Phosphorum                                                  |
    | @author    Piotr Gasiorowski <p.gasiorowski@vipserv.org>               |
    | @copyright 2013-2014 Phalcon Team                                      |
    | @license   New BSD License                                             |
    | @link      https://github.com/phalcon/forum                            |
    +------------------------------------------------------------------------+
*/

namespace Phosphorum;

use Engine\Bootstrap as EngineBootstrap;
use Engine\Application as EngineApplication;
use Engine\Dispatcher;
use Engine\Plugin\DispatchErrorHandler;
use Engine\View;
use Phalcon\Config;
use Phalcon\DiInterface;
use Phalcon\Events\Event;
use Phalcon\Events\Manager;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Manager as ModelsManager;
use Phalcon\Mvc\Model\Metadata\Strategy\Introspection;
use Phalcon\Mvc\Model\Metadata\Strategy\Annotations;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Url as UrlResolver;

use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Dispatcher\Exception as DispatchException;

/**
 * Phosphorum Bootstrap
 */
class Bootstrap extends EngineBootstrap
{
    const
        /**
         * URL prefix
         */
        URL_ROUTE = '/forum';

    const
        /**
         * Db tables prefix
         */
        DB_PREFIX = 'phosphorum_';


    /**
     * Current module name.
     *
     * @var string
     */
    protected $_moduleName = "Phosphorum";

    /**
     * Modules directory
     *
     * @var string
     */
    protected $_moduleDir = '';

    /**
     * Bootstrap construction.
     *
     * @param DiInterface $di Dependency injection.
     * @param Manager     $em Events manager object.
     */
    public function __construct($di, $em)
    {
        parent::__construct($di, $em);

        $this->_moduleDir = realpath($this->getModuleDirectory());

        /**
         * Attach this bootstrap for all application initialization events.
         */
        $em->attach('init', $this);
    }


    /**
     * Register the services.
     *
     * @return void
     */
    public function registerServices()
    {
        $di = $this->getDI();
        $config = $this->getConfig();

        // todo: not sure how to pass this to view from here
        $di->set('forumContent', function() use($di) {
            return $this->_initForum($di);
        });

        parent::registerServices();
    }

    private function _initForum(DiInterface $eyeDi)
    {
        /**
         * @var Router $router
         */

        isset($_GET['_url']) or ($_GET['_url'] = '/');

        define('APP_PATH', $this->_moduleDir);

        /**
         * Read the configuration
         */
        $config = include APP_PATH . "/app/config/config.example.php";

        $config->application->production->baseUri        = self::URL_ROUTE .'/';
        $config->application->production->staticBaseUri  = self::URL_ROUTE .'/';
        $config->application->development->baseUri       = self::URL_ROUTE .'/';
        $config->application->development->staticBaseUri = self::URL_ROUTE .'/';

        /**
         * Include the loader
         */
        require APP_PATH . "/app/config/loader.php";

        $di = new FactoryDefault();

        /**
         * Include the application services
         */
        require APP_PATH . "/app/config/services.php";

        $em = $di->get('eventsManager');
        $view = $di->get('view');
        $router = $di->get('router');

        /**
         * Share db connection and session
         */
        $di->set('session', $eyeDi->get('session'));
        $di->set('db', $eyeDi->get('db'));
        $di->set('router', function () use ($router) {
            // Rewrite routes
            $routes = $router->getRoutes();
            $router->clear();
            foreach($routes as $route) {
                $router->add(self::URL_ROUTE . $route->getPattern(), $route->getPaths());
            }
            return $router;
        });

        $di->set('modelsManager', function() use ($em, $di) {
            $modelsManager = new \Phalcon\Mvc\Model\Manager();
            $modelsManager->setDI($di);
            $modelsManager->setEventsManager($em);

            $em->attach('modelsManager', function (Event $event, ModelsManager $manager, ModelInterface $model) {
                $manager->setModelSource($model, self::DB_PREFIX . $model->getSource());
            });

            return $modelsManager;
        });

        $view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_LAYOUT);

        /**
         * Handle the request
         **/
        $application = new Application($di);

        return $application->handle()->getContent();
    }
}
