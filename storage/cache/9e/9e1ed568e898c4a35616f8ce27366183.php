<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* install/step_4.twig */
class __TwigTemplate_2fa70de4e1c30cc24a649720ae032cfa extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo ($context["header"] ?? null);
        echo "
<div class=\"container\">
  <header>
    <div class=\"row\">
      <div class=\"col-sm-6\">
        <h1 class=\"pull-left\">4<small>/4</small></h1>
        <h3>";
        // line 7
        echo ($context["heading_title"] ?? null);
        echo "<br>
          <small>";
        // line 8
        echo ($context["text_step_4"] ?? null);
        echo "</small></h3>
      </div>
      <div class=\"col-sm-6\">
        <div id=\"logo\" class=\"pull-right hidden-xs\"><img src=\"view/image/logo.png\" alt=\"ocStore\" title=\"ocStore\" /></div>
      </div>
    </div>
  </header>
  ";
        // line 15
        if (($context["success"] ?? null)) {
            // line 16
            echo "  <div class=\"alert alert-success alert-dismissible\">";
            echo ($context["success"] ?? null);
            echo "</div>
  ";
        }
        // line 18
        echo "  <div class=\"alert alert-danger alert-dismissible\"><i class=\"fa fa-exclamation-circle\"></i> ";
        echo ($context["error_warning"] ?? null);
        echo "
    <button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>
  </div>
  <div class=\"visit\">
    <div class=\"row\">
      <div class=\"col-sm-5 col-sm-offset-1 text-center\">
        <p><i class=\"fa fa-shopping-cart fa-5x\"></i></p>
        <a href=\"../\" class=\"btn btn-secondary\">";
        // line 25
        echo ($context["text_catalog"] ?? null);
        echo "</a></div>
      <div class=\"col-sm-5 text-center\">
        <p><i class=\"fa fa-cog fa-5x white\"></i></p>
        <a href=\"../admin/\" class=\"btn btn-secondary\">";
        // line 28
        echo ($context["text_admin"] ?? null);
        echo "</a></div>
    </div>
  </div>
  <div class=\"modules\">
    <div class=\"row\" id=\"extension\">
      <h2 class=\"text-center\"><i class=\"fa fa-circle-o-notch fa-spin\"></i> ";
        // line 33
        echo ($context["text_loading"] ?? null);
        echo "</h2>
    </div>
    <div class=\"row\">
      <div class=\"col-sm-12 text-center\"><a href=\"https://opencartforum.com/files/&utm_source=ocstore_install&utm_medium=store_link&utm_campaign=ocstore_install\" target=\"_BLANK\" class=\"btn btn-default\">";
        // line 36
        echo ($context["text_extension"] ?? null);
        echo "</a></div>
    </div>
  </div>
  <div class=\"mailing\">
    <div class=\"row\">
      <div class=\"col-sm-12\"><i class=\"fa fa-envelope-o fa-5x\"></i>
        <h3>";
        // line 42
        echo ($context["text_mail"] ?? null);
        echo "<br>
          <small>";
        // line 43
        echo ($context["text_mail_description"] ?? null);
        echo "</small></h3>
        <a href=\"https://ocstore.com/subscribe/\" target=\"_BLANK\" class=\"btn btn-secondary\">";
        // line 44
        echo ($context["button_mail"] ?? null);
        echo "</a></div>
    </div>
  </div>
  <div class=\"core-modules\">
    <div class=\"row\">
      <div class=\"col-sm-6 text-center\"><img src=\"view/image/openbay_pro.gif\" />
        <p>";
        // line 50
        echo ($context["text_openbay"] ?? null);
        echo "</p>
        <a class=\"btn btn-primary\" href=\"";
        // line 51
        echo ($context["openbay"] ?? null);
        echo "\">";
        echo ($context["button_setup"] ?? null);
        echo "</a></div>
      <div class=\"col-sm-6 text-center\"><img src=\"view/image/maxmind.gif\" />
        <p>";
        // line 53
        echo ($context["text_maxmind"] ?? null);
        echo "</p>
        <a class=\"btn btn-primary\" href=\"";
        // line 54
        echo ($context["maxmind"] ?? null);
        echo "\">";
        echo ($context["button_setup"] ?? null);
        echo "</a></div>
    </div>
  </div>
  <div class=\"support text-center\">
    <div class=\"row\">
      <div class=\"col-sm-4\"><a href=\"https://www.facebook.com/ocstore\" class=\"icon transition\"><i class=\"fa fa-facebook fa-4x\"></i></a>
        <h3>";
        // line 60
        echo ($context["text_facebook"] ?? null);
        echo "</h3>
        <p>";
        // line 61
        echo ($context["text_facebook_description"] ?? null);
        echo "</p>
        <a href=\"https://www.facebook.com/ocstore\">";
        // line 62
        echo ($context["text_facebook_visit"] ?? null);
        echo "</a></div>
\t  <div class=\"col-sm-4\"><a href=\"https://vk.com/ocstore\" class=\"icon transition\"><i class=\"fa fa-vk fa-4x\"></i></a>
        <h3>";
        // line 64
        echo ($context["text_vkontakte"] ?? null);
        echo "</h3>
        <p>";
        // line 65
        echo ($context["text_vkontakte_description"] ?? null);
        echo "</p>
        <a href=\"https://vk.com/ocstore\">";
        // line 66
        echo ($context["text_vkontakte_visit"] ?? null);
        echo "</a></div>
      <div class=\"col-sm-4\"><a href=\"https://opencartforum.com/?utm_source=ocstore_install&utm_medium=forum_link&utm_campaign=ocstore_install\" class=\"icon transition\"><i class=\"fa fa-comments fa-4x\"></i></a>
        <h3>";
        // line 68
        echo ($context["text_forum"] ?? null);
        echo "</h3>
        <p>";
        // line 69
        echo ($context["text_forum_description"] ?? null);
        echo "</p>
        <a href=\"https://opencartforum.com/?utm_source=ocstore_install&utm_medium=forum_link&utm_campaign=ocstore_install\">";
        // line 70
        echo ($context["text_forum_visit"] ?? null);
        echo "</a></div>
    </div>
  </div>
</div>
";
        // line 74
        echo ($context["footer"] ?? null);
        echo "
<script type=\"text/javascript\"><!--
\$(document).ready(function() {
\t\$.ajax({
\t\turl: '";
        // line 78
        echo ($context["extension"] ?? null);
        echo "',
\t\ttype: 'post',
\t\tdataType: 'json',
\t\tsuccess: function(json) {
\t\t\tif (json['extensions']) {
\t\t\t\thtml  = '';

\t\t\t\tfor (i = 0; i < json['extensions'].length; i++) {
\t\t\t\t\textension = json['extensions'][i];

\t\t\t\t\thtml += '<div class=\"col-sm-6 module\">';
\t\t\t\t\thtml += '  <a class=\"thumbnail pull-left\" href=\"' + extension['href'] + '\"><img src=\"' + extension['image'] + '\" alt=\"' + extension['name'] + '\" /></a>';
\t\t\t\t\thtml += '  <h5>' + extension['name'] + '</h5>';
\t\t\t\t\thtml += '  <p>' + extension['price'] + ' <a target=\"_BLANK\" href=\"' + extension['href'] + '\">";
        // line 91
        echo ($context["text_view"] ?? null);
        echo "</a></p>';
\t\t\t\t\thtml += '  <div class=\"clearfix\"></div>';
\t\t\t\t\thtml += '</div>';
\t\t\t\t}

\t\t\t\t\$('#extension').html(html);
\t\t\t} else {
\t\t\t\t\$('#extension').fadeOut();
\t\t\t}
\t\t}
\t});
});
//--></script>
";
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "install/step_4.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable()
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo()
    {
        return array (  215 => 91,  199 => 78,  192 => 74,  185 => 70,  181 => 69,  177 => 68,  172 => 66,  168 => 65,  164 => 64,  159 => 62,  155 => 61,  151 => 60,  140 => 54,  136 => 53,  129 => 51,  125 => 50,  116 => 44,  112 => 43,  108 => 42,  99 => 36,  93 => 33,  85 => 28,  79 => 25,  68 => 18,  62 => 16,  60 => 15,  50 => 8,  46 => 7,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "install/step_4.twig", "/var/www/rvd/data/www/rvd.dev.webdes.by/install/view/template/install/step_4.twig");
    }
}
