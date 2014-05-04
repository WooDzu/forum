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

use Engine\Exception;
use Engine\Installer as EngineInstaller;
use Engine\Package\Manager as PackageManager;

/**
 * Phosphorum Installer.
 */
class Installer extends EngineInstaller
{
    CONST
        /**
         * Current package version.
         */
        CURRENT_VERSION = '0.4.0';

    const
        /**
         * Db tables prefix
         */
        DB_PREFIX = 'phosphorum_';

    /**
     * Install database entities and files
     *
     * @return void
     */
    public function install()
    {
        /**
         * database tables need renaming since they conflict with PhalconEye
         */
        $tempDirectory = $this->_getTempDirectory();
        $schema = file_get_contents($tempDirectory .'/schemas/forum.sql');

        $newSchema = str_replace('DROP TABLE IF EXISTS `', 'DROP TABLE IF EXISTS `'. self::DB_PREFIX, $schema);
        $newSchema = str_replace('CREATE TABLE `', 'CREATE TABLE `'. self::DB_PREFIX, $newSchema);

        $db = $this->getDI()->get('db');
        $db->begin();
        $db->query($newSchema);
        $db->commit();

        // todo: copy Controller, View
        // todo: Create assets
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

    /**
     * Get directory where the package has been extracted
     *
     * todo: at the moment there is no way to retrieve tempDirectory at this point
     *
     * @return string
     */
    private function _getTempDirectory()
    {
        $packageManager = new PackageManager();

        $tempDir = rtrim($packageManager->getTempDirectory(false), '/\\');
        $manifestLocation = $tempDir . DS . PackageManager::PACKAGE_MANIFEST_NAME;

        // check manifest existence in expected location or its subdir
        if (!file_exists($manifestLocation) && count($tempDirFolders = glob($tempDir . '/*', GLOB_ONLYDIR)) == 1) {
            $tempDir = realpath($tempDirFolders[0]);
        }

        return $tempDir;
    }
}
