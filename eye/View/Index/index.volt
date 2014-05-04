
{% extends "../../Core/View/layouts/main.volt" %}

{% block title %} Forum {% endblock %}

{% block content %}

  {{ forumContent }}

  <script type="application/javascript">
    document.addEventListener('DOMContentLoaded', function(){
        Forum.initializeView('{{ url() ~ forumURI }}');
    }, false );
  </script>
{% endblock %}
