{#
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
#}

{% extends "../../Core/View/layouts/main.volt" %}

{% block title %} Forum {% endblock %}

{% block content %}

  {{ forumContent }}

  <script type="application/javascript">
    document.addEventListener('DOMContentLoaded', function(){
        Forum.initializeView('{{ forumURI }}');
    }, false );
  </script>
{% endblock %}
