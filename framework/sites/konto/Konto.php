<?php

namespace Sites;

/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Konto extends \Controller {

    private $visible;
    private $fields;

    public function __construct($tpl="", $sub="") {
        parent::__construct($tpl,$sub);
        $this->app->addLocation("konto", "mein Konto");
        $this->app->getUser()->update();
    }

    public function indexAction() {
        require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }
    
    public function loginAction()
    {
        $this->disableRender();
        if (!$this->request->post["username"]) $this->app->forward($this->request->referrer);
        if ($this->app->login(
            $this->request->post["username"],
            $this->request->post["password"],
            $this->request->post["cookie"]
        )) {
            $this->app->addSuccess("Sie haben sich erfolgreich eingeloggt.");
        } else {
            $this->app->addError("Login fehlgeschlagen.");
        }
        $this->app->forward($this->request->referrer);
    }

    public function logoutAction()
    {
        $this->disableRender();
        $this->app->logout();
        $this->app->forward("");
    }

    public function validateAction()
    {
        require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }

    public function revalidateAction() {
        require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }
        
    public function registerAction() {
        require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }
    
    public function profileditAction() {
        require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }
	
    public function profilformAction() {
        require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }
        
    public function saveprofileAction() {
        require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }

    public function checkusernameAction($user = "", $ajax=true) {
        require_once(__DIR__."/includes/".__FUNCTION__.".php");
        return $return1;
    }
        
    public function saveusernameAction() {
        require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }
        
    public function savepasswordAction() {
        require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }
        
    public function setemailAction() {
        require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }
        
    public function passwordrequestAction() {
        $this->app->forward("");
        require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }

    public function resetpasswordAction() {
        $this->app->forward("");
        require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }

}

