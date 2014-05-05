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

use Core\Model\Settings;
use Engine\Bootstrap as EngineBootstrap;
use Phalcon\Config;
use Phalcon\Di;
use Phalcon\DiInterface;
use Phalcon\DI\FactoryDefault;
use Phalcon\Events\Event;
use Phalcon\Events\Manager;
use Phalcon\Http\Request;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Manager as ModelsManager;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Router\Route;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Mvc\View;
use Phalcon\Loader;

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
     * Bootstrap construction.
     *
     * @param DiInterface $di Dependency injection.
     * @param Manager     $em Events manager object.
     */
    public function __construct($di, $em)
    {
        parent::__construct($di, $em);

        $config = $this->getConfig();
        $this->_moduleDir = realpath($this->getModuleDirectory());
        $this->_moduleUri = $config->application->baseUrl . self::URL_ROUTE;
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

        $registry = $di->get('registry');
        $registry->forum = array (
            'content' => $this->_initForum($di, $config),
            'moduleURI' => $this->_moduleUri . '/',
        );

        parent::registerServices();
    }

    /**
     * Initializes and dispatchers forum
     *
     * @param DiInterface $eyeDI
     * @param Config $eyeConfig
     *
     * @return string Rendered forum contents
     */
    protected function _initForum(DiInterface $eyeDI, Config $eyeConfig)
    {
        isset($_GET['_url']) or ($_GET['_url'] = '/');

        define('APP_PATH', $this->_moduleDir);

         // create configuration
        $config = $this->_createConfiguration($eyeDI, $eyeConfig);

        // register dependencies
        $this->_registerLibraries($config);

        // initialize forum's DI and set it as default
        $di = new FactoryDefault();
        DI::setDefault($di);


        /**
         * Include forum services
         */
        require $this->_moduleDir . "/app/config/services.php";

        $em = $di->get('eventsManager');
        $router = $di->get('router');

        // share session service
        $di->set('session', $eyeDI->get('session'));

        // share db connection
        $di->set('db', $eyeDI->get('db'));

        // rewrite routes
        $di->set('router', function () use ($router, $eyeConfig) {
            $routes = $router->getRoutes();
            $router->clear();

            /** @var Route $route **/
            foreach($routes as $route) {
                $router->add($this->_moduleUri . $route->getPattern(), $route->getPaths());
            }
            return $router;
        });

        // modelsManager must use renamed table names
        $di->set('modelsManager', function() use ($em, $di) {
            $em->attach('modelsManager:afterInitialize', function(Event $event, ModelsManager $manager, Model $model) {
                if (strpos($class = get_class($model), 'Phosphorum\Models\\') === 0) {
                    $manager->setModelSource($model, self::DB_PREFIX . $model->getSource());
                }
            });
            $modelsManager = new ModelsManager();
            $modelsManager->setEventsManager($em);
            return $modelsManager;
        });

        // render layout only
        $di->get('view')->setRenderLevel(View::LEVEL_LAYOUT);

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

        // restore default DI
        DI::setDefault($eyeDI);

        return $buff;
    }

    /**
     * Creates forum configuration
     *
     * @var DiInterface $eyeDI
     * @var Config $eyeConfig
     *
     * return array
     */
    protected function _createConfiguration(DiInterface $eyeDI, Config $eyeConfig)
    {
        /** @var Request $request */
        $request = $eyeDI->get('app')->request;

        $config = include $this->_moduleDir . "/app/config/config.example.php";

        $config->application->debug = $eyeConfig->application->debug;
        $config->application->production->baseUri        = $this->_moduleUri .'/';
        $config->application->production->staticBaseUri  = $eyeConfig->application->baseUrl;
        $config->application->development->baseUri       = $this->_moduleUri .'/';
        $config->application->development->staticBaseUri = $eyeConfig->application->baseUrl;

        $config->beanstalk->host      = Settings::getSetting('phosphorum_beanstalk_host');
        $config->github->clientId     = Settings::getSetting('phosphorum_github_client_id');
        $config->github->clientSecret = Settings::getSetting('phosphorum_github_client_secret');
        $config->github->redirectUri  = $request->getScheme() .'://'. $request->getHttpHost() .
            $this->_moduleUri .'/login/oauth/access_token/';

        return $config;
    }

    /**
     * Include the loader and register libraries
     *
     * @var Config $config
     *
     * return void
     */
    protected function _registerLibraries(Config $config)
    {
        $librariesDir = $registry = $this->getDI()->get('registry')->directories->libraries;

        /** @var Loader $loader **/
        require $this->_moduleDir . "/app/config/loader.php";

        $loader->registerNamespaces(
            array(
                'Guzzle' => $librariesDir .'Guzzle/Guzzle',
                'Ciconia' => $librariesDir .'/Ciconia/Ciconia',
                'Symfony\Component\EventDispatcher' => $librariesDir .'/Symfony-eventdispather',
                'Symfony\Component\OptionsResolver' => $librariesDir .'/Symfony-optionsresolver',
            ),
            true
        );
    }

}
