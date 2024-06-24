<?php
namespace Blocks;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Login extends \Controller
{
   
    public function indexAction()
    {
        $this->app->registerCss("res/css/login.css");
        $user = $this->app->getUser();
        if ($user->isLoggedIn())
        {
            $content = new \Controller(dirname(__FILE__)."/tpl_userinfo.html");
            $content->USERNAME = $user->username;
        } else {
            $content = new \Controller(dirname(__FILE__)."/tpl_login.html");
        }
        
        $content->render();
        $this->CONTENT = $content->getTemplate();
    }
}

?>
