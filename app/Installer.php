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

use Engine\Installer as EngineInstaller;

/**
 * Phosphorum Installer.
 *
 * @category  PhalconEye
 * @package   Phosphorum
 * @author    Piotr Gasiorowski <p.gasiorowski@vipserv.org>
 * @copyright 2013-2014 Phalcon Team
 * @license   New BSD License
 * @link      https://github.com/phalcon/forum
 */
class Installer extends EngineInstaller
{
    CONST
        /**
         * Current package version.
         */
        CURRENT_VERSION = '0.4.0';

    /**
     * Used to install specific database entities or other specific action.
     *
     * @return void
     */
    public function install()
    {
        // Nasty method to copy over controllers to Controller dir
        mkdir(__DIR__ .'/Controller');
        foreach (glob(__DIR__ . DS .'controllers'. DS .'*Controller.php') as $path) {
            copy($path, str_replace(__DIR__ . DS .'controllers', __DIR__ . DS .'Controller', $path));
        }

        // $this->runSqlFile(__DIR__ . '/Assets/sql/installation.sql');
    }


    /**
     * Used before package will be removed from the system.
     *
     * @return void
     */
    public function remove()
    {

    }

    /**
     * Used to apply some updates.
     * Return 'string' (new version) if migration is not finished, 'null' if all updates were applied.
     *
     * @param string $currentVersion Current module version.
     *
     * @return string|null
     */
    public function update($currentVersion)
    {
        return $currentVersion = null;
    }

}
