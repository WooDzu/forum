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
*/

namespace Phosphorum;

use Engine\Bootstrap as EngineBootstrap;
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

/**
 * Phosphorum Bootstrap.
 *
 * @category  PhalconEye
 * @package   Phosphorum
 * @author    Piotr Gasiorowski <p.gasiorowski@vipserv.org>
 * @copyright 2013-2014 Phalcon Team
 * @license   New BSD License
 * @link      https://github.com/phalcon/forum
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
     * Phosphorum Metadata Strategy
     *
     * @var Introspection
     */
    private $_strategyForum = null;

    /**
     * PhalconEye Metadata Strategy
     *
     * @var Annotations
     */
    private $_strategyEye = null;

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
         * Alias classes
         */
        foreach (glob($this->_moduleDir .'/Controller/*Controller.php') as $path) {

            $controller = str_replace($this->_moduleDir .'/Controller/', '', $path);
            $className = substr($controller, 0, -4);
            require_once 'Controller/'. $controller;

            class_alias('Phosphorum\Controllers\\'. $className,
                        'Phosphorum\Controller\\'. $className, false);
        }

        /**
         * Attach this bootstrap for all application initialization events.
         */
        $em->attach('init', $this);
    }

    /**
     * Copy forum routes to engine Router
     *
     * @param Event $event
     */
    public function afterRouter()
    {
        if ($forumRouters = include $this->_moduleDir . '/config/routes.php') {
            /**
             * @var \Phalcon\Mvc\Router $engineRouter
             * @var \Phalcon\Mvc\Router\Route $route
             **/
            $di = $this->getDI();
            $engineRouter = $di->get('router');

            foreach($forumRouters->getRoutes() as $route) {
                $paths = $route->getPaths();
                $paths['module'] = 'phosphorum';
                $paths['namespace'] = 'Phosphorum\Controller';
                $engineRouter->add(self::URL_ROUTE . $route->getPattern(), $paths);
            }
        }
    }


    /**
     * Register the services.
     *
     * @return void
     */
    public function registerServices()
    {
        $di = $this->getDI();
        $em = $this->getEventsManager();
        $config = $this->getConfig();

        /**
         * Initialize dispatcher.
         **/
        $em->attach("dispatch:beforeException", new DispatchErrorHandler());

        $dispatcher = new Dispatcher();
        $dispatcher->setEventsManager($em);
        $di->set('dispatcher', $dispatcher);

        /**
         * Create forum Config
         */
        define('APP_PATH', $this->_moduleDir);

        $config = $this->_mergeConfigs($di, $config);

        /**
         * Register namespaces
         */
        $di->get('loader')->registerNamespaces(
            array(
                'Phosphorum\Models'      => $config->application->modelsDir,
                'Phosphorum\Controllers' => $config->application->controllersDir,
                'Phosphorum\Markdown'    => $config->application->libraryDir . 'Markdown',
                'Phosphorum\Github'      => $config->application->libraryDir . 'Github',
                'Phosphorum\Mail'        => $config->application->libraryDir . 'Mail',
            ),
            true
        );


        /**
         * Initialize View
         */
        $this->_initView($di, $em, $config);


        /**
         * Initialize Models Manager
         */
        $em->attach('modelsManager', $this);

        /**
         * Initialize MetaData adapter
         */
        $modelsMetadata = $di->get('modelsMetadata');
        $this->_strategyEye = $modelsMetadata->getStrategy();
        $this->_strategyForum = new Introspection();
        $modelsMetadata->setStrategy($this);
    }

    /**
     * Merge forum and engine configs
     *
     * @param DiInterface $di
     * @param Config $engineConfig
     *
     * @todo: cms configuration for 3rd party services ie. smtp, beanstalk etc....
     * @return Config
     */
    private function _mergeConfigs(DiInterface $di, Config $engineConfig)
    {
        if ($forumConfig = include $this->_moduleDir . '/config/config.example.php') {
            $forumConfig->merge($engineConfig);
        }

        $forumConfig->application->controllersDir = $this->_moduleDir . DS .'Controller'. DS;
        $forumConfig->application->modelsDir      = $this->_moduleDir . DS .'models'. DS;
        $forumConfig->application->viewsDir       = $this->_moduleDir . DS .'views'. DS;
        $forumConfig->application->pluginsDir     = $this->_moduleDir . DS .'plugins'. DS;
        $forumConfig->application->libraryDir     = $this->_moduleDir . DS .'library'. DS;

        $di->set('config', $forumConfig);

        return $forumConfig;
    }


    /**
     * Setting up the view component
     *
     * @param DiInterface $di
     * @param Manager $em
     * @param Config $config
     *
     * @return void
     */
    private function _initView(DiInterface $di, Manager $em, Config $config)
    {
        $di->set(
            'view',
            function () use ($di, $em, $config) {
                return View::factory($di, $config, $this->_moduleDir .'/views/', $em);
            }
        );
    }


    /******************************************************
     * @todo: this is sooo cool that's its possible - but rather bad strategy further
     ******************************************************/

    public function afterInitialize(Event $event, ModelsManager $manager, ModelInterface $model) {
        if (strpos($class = get_class($model), 'Phosphorum\Models\\') === 0) {
            $manager->setModelSource($model, self::DB_PREFIX . $model->getSource());
        }
    }

    public function getMetaData(ModelInterface $model, DiInterface $di) {
        if (strpos($class = get_class($model), 'Phosphorum\Models\\') === 0) {
            return $this->_strategyForum->getMetaData($model, $di);
        } else {
            return $this->_strategyEye->getMetaData($model, $di);
        }
    }

    public function getColumnMaps(ModelInterface $model, DiInterface $di) {
        if (strpos($class = get_class($model), 'Phosphorum\Models\\') === 0) {
            return $this->_strategyForum->getColumnMaps($model, $di);
        } else {
            return $this->_strategyEye->getColumnMaps($model, $di);
        }
    }
}
