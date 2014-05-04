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

namespace Phosphorum\Controller;

use Core\Controller\AbstractController;

/**
 * Index controller.
 *
 * @RoutePrefix("/forum")
 */
class IndexController extends AbstractController
{
    /**
     * @Route("(/.*)*", methods={"GET", "POST"}, name="forum")
     */
    public function indexAction()
    {
        $this->view->forumURI = 'forum/';
        $this->view->forumContent = $this->getDI()->get('forumContent');
    }

    /**
     * Setup assets.
     *
     * @return void
     */
    protected function _setupAssets()
    {
        parent::_setupAssets();

        $this->assets->addCss('//cdn.jsdelivr.net/bootstrap/3.1.1/css/bootstrap.min.css', false);
        $this->assets->addCss('//cdn.jsdelivr.net/prettify/0.1/prettify.css', false);
        $this->assets->addCss('assets/css/phosphorum/theme.css');
        $this->assets->addCss('assets/css/phosphorum/editor.css');
        $this->assets->addCss('assets/css/phosphorum/diff.css');
        $this->assets->addCss('assets/css/phosphorum/style.css');

        $this->assets->addJs('//cdn.jsdelivr.net/g/bootstrap@3.1,prettify@0.1(prettify.js+lang-css.js+lang-sql.js)', false);
        $this->assets->addJs('assets/js/phosphorum/editor.js');
        $this->assets->addJs('assets/js/phosphorum/forum.js');
        $this->assets->addJs('assets/js/phosphorum/gs.js');
    }
}
