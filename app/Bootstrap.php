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
use Phalcon\Config;
use Phalcon\DiInterface;
use Phalcon\DI\FactoryDefault;
use Phalcon\Events\Event;
use Phalcon\Events\Manager;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Manager as ModelsManager;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Router\Route;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Mvc\View;
use Phalcon\Loader;
use Phalcon\Mvc\Model\Metadata\Strategy\Introspection;
use Phalcon\Mvc\Model\Metadata\Strategy\Annotations;

/**
 * Phosphorum Bootstrap
 */
class Bootstrap extends EngineBootstrap
{
    const
        /**
         * URL prefix
         */
        URL_ROUTE = 'forum';

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
     * Libraries directory
     *
     * @var string
     */
    protected $_librariesDir = '';

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
        $this->_librariesDir = ROOT_PATH.'/app/libraries';
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

        // todo: Not sure how to pass forum's contents to CMS's view from here
        $di->set('forumContent', function() use($di, $em) {

            // todo: Decouple composer loaded libraries... maybe library package(s)?
            // include $this->_librariesDir .'/autoload.php';

            // In the end it created pretty nice lazy initialization
            return $this->_initForum($di, $em);
        });

        parent::registerServices();
    }

    /**
     * Initializes and dispatchers forum
     *
     * @param DiInterface $eyeDI
     * @param Manager $eyeEM
     *
     * @return string Rendered forum contents
     */
    protected function _initForum(DiInterface $eyeDI, Manager $eyeEM)
    {
        $cms = $this->getConfig();

        isset($_GET['_url']) or ($_GET['_url'] = '/');

        define('APP_PATH', $this->_moduleDir);

        /**
         * Create configuration
         */
        $config = include APP_PATH . "/app/config/config.example.php";

        $config->application->debug = $cms->application->debug;
        $config->application->production->baseUri        = $cms->application->baseUrl . self::URL_ROUTE .'/';
        $config->application->production->staticBaseUri  = $cms->application->baseUrl;
        $config->application->development->baseUri       = $cms->application->baseUrl . self::URL_ROUTE .'/';
        $config->application->development->staticBaseUri = $cms->application->baseUrl;

        // todo: add cms form
        $config->beanstalk->host      = $cms->database->host;
        $config->github->clientId     = '2b052673bcb7eff47be0';
        $config->github->clientSecret = 'b233703ca22a12e268f6276fd8b39e0af7fa538f';
        $config->github->redirectUri  = $_SERVER['REQUEST_SCHEME'] .'://'. $_SERVER['SERVER_NAME'] .
            $cms->application->baseUrl . self::URL_ROUTE .'/login/oauth/access_token/';

        /**
         * Include the loader
         *
         * @var Loader $loader
         */
        require APP_PATH . "/app/config/loader.php";

        $this->_registerLibraries($loader);

        $di = new FactoryDefault();

        /**
         * Include the application services
         */
        require APP_PATH . "/app/config/services.php";

        $queue = $di->get('queue');
        $em = $di->get('eventsManager');
        $tag = $di->get('tag');
        $view = $di->get('view');
        $router = $di->get('router');

        /**
         * Reconfigure forum services
         */
        $di->set('session', $eyeDI->get('session'));

        $di->set('db', $eyeDI->get('db'));

        $di->set('router', function () use ($router, $cms) {
            // Rewrite routes
            $routes = $router->getRoutes();
            $router->clear();
            /** @var Route $route **/
            foreach($routes as $route) {
                $router->add($cms->application->baseUrl . self::URL_ROUTE . $route->getPattern(), $route->getPaths());
            }
            return $router;
        });

        $di->set('modelsManager', function() use ($em, $di) {
            $modelsManager = new ModelsManager();
            $modelsManager->setDI($di);
            $modelsManager->setEventsManager($em);
            return $modelsManager;
        });

        $tag->setDI($di);
        $view->setRenderLevel(View::LEVEL_LAYOUT);

        /**
         * Handle multiple DI's issue
         * @see: http://forum.phalconphp.com/discussion/2225/multiple-service-containers
         */
        $em->attach('modelsManager:afterInitialize', $this);
        $eyeEM->attach('modelsManager:afterInitialize', $this);

        // Initialize dynamic MetaData adapter
        $modelsMetadata = $eyeDI->get('modelsMetadata');
        $this->_strategyEye = $modelsMetadata->getStrategy();
        $this->_strategyForum = new Introspection();
        $modelsMetadata->setStrategy($this);

        // Attach queue service to the CMS
        $eyeDI->set('queue', $di->get('queue'));


        /**
         * Handle the request
         **/
        $application = new Application($di);

        $buff = $application->handle()->getContent();

        if ($application->request->isAjax()) {
            $application->response->sendHeaders();
            $application->response->setContent($buff);
            $application->response->send() && die();
        }

        return $buff;
    }

    /**
     * Registering libraries
     *
     * @var Loader $loader
     *
     * return void
     */
    protected function _registerLibraries(Loader $loader)
    {
        $loader->registerNamespaces(
            array(
                'Guzzle' => $this->_librariesDir .'/Guzzle/Guzzle',
                'Ciconia' => $this->_librariesDir .'/Ciconia/Ciconia',
                'Symfony\Component\EventDispatcher' => $this->_librariesDir .'/Symfony-eventdispather',
                'Symfony\Component\OptionsResolver' => $this->_librariesDir .'/Symfony-optionsresolver',
            ),
            true
        );
    }

    /******************************************************
     * @todo: this is sooo cool that it's possible - but rather bad pattern further
     * even if we're using two separate DI's some models won't use metadataStrategy of the correct one
     ******************************************************/

    public function afterInitialize(Event $event, ModelsManager $manager, Model $model) {
        if (strpos($class = get_class($model), 'Phosphorum\Models\\') === 0) {
            $manager->setModelSource($model, self::DB_PREFIX . $model->getSource());
        }
    }

    public function getMetaData(Model $model, DiInterface $di) {
        if (strpos($class = get_class($model), 'Phosphorum\Models\\') === 0) {
            return $this->_strategyForum->getMetaData($model, $di);
        } else {
            return $this->_strategyEye->getMetaData($model, $di);
        }
    }

    public function getColumnMaps(Model $model, DiInterface $di) {
        if (strpos($class = get_class($model), 'Phosphorum\Models\\') === 0) {
            return $this->_strategyForum->getColumnMaps($model, $di);
        } else {
            return $this->_strategyEye->getColumnMaps($model, $di);
        }
    }
}
