<?php

/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Master extends Controller
{
	private $User;
	private $content;

	public function indexAction()
	{
		$this->app->registerCSS("res/node_modules/bootstrap/dist/css/bootstrap.min.css");
		$this->app->registerCSS("res/css/fonts.css");
		
		if ($this->acl->checkRight(S_ADMIN)) {
			$this->app->registerCSS("res/css/debug.css");
		}

		$this->app->registerJS("res/node_modules/jquery/dist/jquery.min.js");
		$this->app->registerJS("res/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js");
		$this->app->registerJS("res/js/framework.js");

		if ($this->app->adminmode()) {
			$this->app->registerJS("res/js/admin.js");
			$this->app->registerCss("body { background: #fda; }", true);
		}

		$dirname = $this->request->site;
	    if (!$dirname && defined("HOME")) {
			$dirname = HOME;
			$this->request->site = HOME;
		}
	    $filename = ucfirst($dirname);
	    $classname = "\\Sites\\".$filename;
		$action = ($this->request->action != "") ? $this->request->action."Action" : "indexAction";

	    if (
		       !file_exists($_SERVER["DOCUMENT_ROOT"].$this->request->root."sites/".$dirname."/".$filename.".php")
			&& !file_exists(FRAMEWORK_DIR . "sites/".$dirname."/".$filename.".php")
		) {
			$tmp = array($this->request->site);
			if ($this->request->action) array_push($tmp, $this->request->action);
			$args = array_merge($tmp, $this->request->args);
			$this->request->args = $args;
			$this->request->site = "cms";
			if ($this->request->ajax) $this->disableRender();
			$this->request->action = "index";
			$action = "indexAction";
			$dirname = "cms";
			$filename = "Cms";
			$classname = "\\Sites\\Cms";
		}
		    
		if (!file_exists("sites/".$dirname."/".$filename.".php") && !file_exists(FRAMEWORK_DIR . "sites/".$dirname."/".$filename.".php"))
		{
			$this->app->forward("oops");
		}

		// Einbindung der Unterseite, die gerade definiert ist
	    require_once("sites/".$dirname."/".$filename.".php");
	    $controller = new $classname("auto");

		$controller->$action();

		$mastertemplate = $this->app->getValue("mastertemplate");
		if ($mastertemplate !== null) {
		    if (file_exists(__DIR__."/".$mastertemplate.".html")) {
                $tpl = file_get_contents(__DIR__ ."/".$mastertemplate . ".html");
                $this->setTemplate($tpl);
            }
        }

		$this->CONTENT = $controller->render();

/** OBERHALB NICHT ÄNDERN !!!!!! **/

		$b = new \Block("header");
		$this->HEADER = $b->render();

		$b = new \Block("footer");
		$this->FOOTER = $b->render();

		$this->SELFURL = "https://".$_SERVER["HTTP_HOST"]."/".$this->request->url;

		$canonical =$this->app->getvalue("canonical");
		if ($canonical) {
			$this->CANONICAL = $canonical;
		} else {
			$this->CANONICAL = "https://".$_SERVER["HTTP_HOST"]."/".$this->request->url;
		}

		$this->render();

	}

}
