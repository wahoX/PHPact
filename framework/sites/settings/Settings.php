<?php
/**
 * \namespace \Sites
 */
namespace Sites;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
use \Controller as Controller;

class Settings extends \Controller
{
	private $user;
	
	public function __construct($template = "", $sub = "")
	{
		parent::__construct($template, $sub);

		$this->user = $this->app->getUser();
		$owner = $this->app->getOwner();
		if (!$this->user->isLoggedIn()) {
			$this->app->forward("404");
		}

		if ($owner != $this->user->id && !$this->acl->checkRight(S_ADMIN))
		{
			$this->app->forward("404");
		}

		$this->app->registerCss("res/css/admin.css");
		$this->app->registerCss("res/css/form.css");
                $this->app->addLocation("settings", "Einstellungen");

        }

	public function indexAction()
	{
		if ($this->app->adminmode())
		{
			$this->app->adminmode("viewer");
			$this->app->forward($this->request->site."/".$this->request->action);
		}

		$settings = $this->app->getSettings();
		$this->app->registerCss("res/css/form.css");
		$this->app->registerJs("res/js/settings.js");
		$this->app->registerJs("res/js/favicon.js");
		$this->app->registerJs("res/js/jquery/jquery.form.js");
		$this->app->registerJs("res/js/jquery/farbtastic/farbtastic.js");
		$this->app->registerCss("res/js/jquery/farbtastic/farbtastic.css");
		$this->app->registerJs("res/js/jquery/jquery.imgareaselect.min.js");
		$this->app->registerCss("res/css/bootstrap-slider.css");
		$this->app->registerJs("res/js/jquery/bootstrap-slider.js");
		$this->app->registerCss("res/css/imgareaselect-animated.css");
		$this->app->registerJs("var ias; var uploadID; var owner; var large_width=0; var large_height=0; var header_width=".$settings["header_width"]."; var header_height=".$settings["header_height"].";", true);

		$font_size = ($settings["font_size"]) ? $settings["font_size"] : "12";
		$pagetitle_size = ($settings["pagetitle_size"]) ? $settings["pagetitle_size"] : "1.5";
		$slogan_size = ($settings["slogan_size"]) ? $settings["slogan_size"] : "0.9";
		$this->app->registerOnLoad("
			initSettings(".$font_size.", ".$pagetitle_size.", ".$slogan_size.");
			initImageForm(".$this->app->getOwner().");
			initLogoForm();
			initFaviconForm();
		");
		
		$fonts = array (
			"sans-serif" => array (
				"Arial",
				"Arial Narrow",
				"Arial Black",
				"Helvetica",
				"Tahoma",
				"Trebuchet MS",
				"Verdana"
			),
			"serif" => array (
				"georgia",
				"Times New Roman"
			),
			"monospace" => array (
				"Andale Mono",
				"Courier New"
			),
			"display" => array (
				"Comic Sans MS",
				"Impact"
			)
		);
		$options = "";
		foreach ($fonts AS $key => $val) {
			if ($options != "") $options .= "</optgroup>";
			$options .= "<optgroup label='".$key."'>";
			foreach ($val AS $font) {
				$tmpfont = $font.", ".$key;
				$selected = ($settings["font_family"] == $tmpfont) ? " selected='selected'" : "";
				$options .= "<option style='font-family: ".$tmpfont."' value='".$tmpfont."'".$selected.">".$font."</option>";
			}
		}
		$options .= "</optgroup>";
		$this->FONT_OPTIONS = $options;

		$form = new \Form($this, "settings");
		$form->registerElement("imprint:name", "any", true, "");
		$form->registerElement("imprint:vorname", "any", true, "");
		$form->registerElement("imprint:strasse", "any", true, "");
		$form->registerElement("imprint:plz", "plz", true, "");
		$form->registerElement("imprint:ort", "any", true, "");
		$form->registerElement("imprint:telefon", "phone", true, "");
		$form->registerElement("imprint:fax", "phone", true, "");
		$form->registerElement("imprint:email", "email", true, "Bitte geben Sie eine gültige E-Mail-Adresse an!"); 	//Email-Adresse
		foreach($settings AS $key => $val) {
			$k = strtoupper($key)."_VALUE";
			$this->$k = $val;
		}
		$color = $settings["maincolor"];
		$this->MAINCOLOR = ($color) ? "#".$color : "#508845";
	}

	public function saveAction()
	{
        
		$settings = $this->request->post;
		$success = true;
		if ($settings["form"] == "settings") {
			$form = new \Form($this, "settings");
			$form->registerElement("imprint:name", "any", true, "");
			$form->registerElement("imprint:vorname", "any", true, "");
			$form->registerElement("imprint:strasse", "any", true, "");
			$form->registerElement("imprint:plz", "plz", true, "");
			$form->registerElement("imprint:ort", "any", true, "");
			$form->registerElement("imprint:telefon", "phone", true, "");
			$form->registerElement("imprint:email", "email", true, "Bitte geben Sie eine gültige E-Mail-Adresse an!"); 	//Email-Adresse
			$form->setFormData($this->request->post);
			$success = $form->run();
		}
		if ($success) {
			if ($settings["maincolor"]) $settings["maincolor"] = str_replace("#", "", $settings["maincolor"]);
			foreach($settings AS $key => $val) {
				if ($key != "toggleId" && $key != "form") $this->db->saveSetting($key, $val);
				if ($key == "toggleId") $this->addAjaxContent($val);
			}
		}
		else $this->addAjaxContent('false');
		if (!$this->request->ajax) $this->app->forward("settings");
	}


		
}
	
?>
