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

namespace Phosphorum\Form;

use Core\Form\CoreForm;

/**
 * Config form.
 */
class ConfigForm extends CoreForm
{

    /**
     * Initialize the config form
     *
     * @return void
     */
    public function initialize()
    {
        $this->addContentFieldSet('GitHub OAuth')
            ->addText('github_client_id', 'Client ID')
            ->addText('github_client_secret', 'Client Secret');

        $this->addContentFieldSet('Beanstalk server')
            ->addText('beanstalk_host', 'Host', null, 'localhost');
    }
}
