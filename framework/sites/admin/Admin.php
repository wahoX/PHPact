<?php
namespace Sites;
use Blocks;

/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
use \Controller as Controller;

class Admin extends \Controller
{
	private $user;
	private $res            = "";
	private $valid_key      = false;

	public function __construct($template = "", $sub = "")
	{
		parent::__construct($template, $sub);

		if (!$this->acl->checkRight(S_ADMIN)) $this->app->forward("oops");

		$this->app->disableCache();
		$this->app->registerCss("res/css/admin.css");
        $this->app->registerJs("res/js/jquery/jquery.form.js");
        $this->app->addLocation("admin", "Administration");
    }
	
	public function indexAction()
	{
		$this->URL = $this->request->referrer;
	}
	
	public function setmodusAction()
	{
		$modus = $this->request->args[0];
		$referrer = $this->request->referrer;
		if ($modus == "admin") {
			$this->app->adminmode("admin");
			if (stristr($referrer, "settings")) $referrer = $this->request->root;
			if (stristr($referrer, "admin/user")) $referrer = $this->request->root;
		}
		else {
			$this->app->adminmode("viewer");
			if (stristr($referrer, "admin/menu")) $referrer = $this->request->root;
		}
		$this->app->forward($referrer);
	}

    public function mailtemplatesAction() {
	    if (!$this->acl->checkRight(S_ADMIN)) $this->app->forward("oops");
		require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }

}
