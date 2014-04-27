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
use Phalcon\DiInterface;
use Phalcon\Events\Manager;

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
    /**
     * Current module name.
     *
     * @var string
     */
    protected $_moduleName = "Phosphorum";

    /**
     * Bootstrap construction.
     *
     * @param DiInterface $di Dependency injection.
     * @param Manager     $em Events manager object.
     */
    public function __construct($di, $em)
    {
        parent::__construct($di, $em);

        /**
         * Alias classes
         */
        foreach (glob(__DIR__ .'/Controller/*Controller.php') as $path) {
            $controller = str_replace(__DIR__ .'/Controller/', '', $path);
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
}
