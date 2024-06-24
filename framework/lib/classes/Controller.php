<?php

/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */

class Controller extends Parser
{
	
	// Werte, die später geparst werden sollen
	public $parserValues = array();
	
	public $db = false; // Instanz der Datenbank-Klasse (DB_Portal)
	public $app; // Instanz der Applikation
	public $request; // Instanz des Request-Objektes
	public $acl; // Instanz der ACL
	
	/**
	 * Constructor des Controllers.
	 *
	 * @PARAM Parser $parser ein Parser-Objekt, das manipuliert werden soll.
	 */
	public function __construct($template = "", $tag="")
	{
		$this->app = Application::getInstance();
		$this->request = Request::getInstance();
		$this->acl = ACL::getInstance();

		$classname = get_class($this);
		if (stristr($classname, "Sites\\") || stristr($classname, "Blocks\\")) {
			echo "Controller: ".get_class($this)."\n";
			$model = str_replace(array("Sites\\", "Blocks\\", "\\"), array("","", ""), $classname);
			$this->loadModel($model);
		}

		if (!$this->db) $this->db = DB::getInstance();

		if ($template == "auto")
		{
			$dir = "sites/".$this->request->site."/";
			$template = $dir.$this->request->action.".html";
			$template = $this->app->findFile($template);
			if (!$template) {
				$action = $this->request->action;
				if (stristr(get_class($this), "Sites\\") && $action != "" && !method_exists($this, $this->request->action."Action")) {
					$this->app->forward("oops");
				}
				$template = $dir."_tpl.html";
			}
        }
        if ($template) echo "Template: ".$template."\n-------------------------\n";

		parent::__construct($template, $tag);

		$this->checkRights();
		$this->buildInlineEditor();
		
		if ($yaml = $this->parseTag("[YAML]")){
			$this->header = $this->getSubtemplate("YAML");
		}
	}
	
	public function __sleep() {
		return array("template", "templateOriginal", "header");
	}

	public function __call($name, $args) {
		if (stristr($name, "Action")) $this->__indexAction($args[0]);
	}
    
	public function __indexAction($args=null) {}
        
    public function query($table=null) {
        if (!$table) $table = str_replace("sites\\", "", strtolower(get_class($this)));
        return new \Query($table);
    }

        /**
         * Diese Funktion lädt ein anderes Model in $this->db
         * 
         * @param string $classname Name des Models
         */
   	public function loadModel($classname) {
   		$classname = ucfirst($classname);
		if (
			file_exists($_SERVER["DOCUMENT_ROOT"].$this->request->root."models/".$classname.".php")
			|| file_exists(FRAMEWORK_DIR . $this->request->root."models/".$classname.".php")
		) {
			require_once("models/".$classname.".php");
			$classname = "\\Models\\".$classname;
			$this->db = $classname::getInstance();
		} 
	}

	/**
	 * Diese Funktion gibt ein Subtemplate als Controller zurück.
	 *
	 * @param String $name Name des Subtemplates - die Klammern [ / ] werden weg gelassen 
	 *
	 * @return Controller der Controller mit dem Subtemplate
	 **/ 
	public function getSubTemplate($name) {
		$tpl = new \Controller();
		$tpl->setTemplate($this->parseTag("[".$name."]"));
		return $tpl;
	}
	
	/**
	 * Funktion, um das Rendern der Seite zu verhindern.
	 * Wenn diese Funktion aufgerufen wird, dann wird in der gesamten Applikation kein Template geparst.
	 * Sinnvoll für Ausgaben bei Ajax-Requests.
	 */
	public function disableRender()
	{
		$this->app->disableRender();
	}
	
	/**
	 * Magic Function zum Setzen von Werten. Die Variablen werden im Array $renderValues gespeichert.
	 * Alle verfügbaren Variablen werden in der Funktion render() in das zugewiesene Template geparst.
	 * Werte können einfach mit $this->variablennamne = "Wert" gesetzt werden.
	 *
	 * @PARAM string $var der Variablenname
	 * @PARAM mixed $val der Wert. Bei verwendung unseres Parsers muss $val ein String sein.
	 */
	public function __set($var, $val)
	{

        if (is_object($val)) {
            if (get_class($val) == "DateTime") {
                $val = $val->format("d.m.Y H:i:s");
            } else if (method_exists($val, "getAllValues")) {
                $val = (array) $val->getAllValues();
            } else if (get_class($val) == "stdClass") {
                $val = (array) $val;
            }
        }
        if (is_array($val)) {
            foreach($val AS $k => $v) {
                $placeholder = $var.".".strtoupper($k);
                $this->__set($placeholder, $v);
            }
            return;
        } 

		$this->parserValues[$var] = $val;
	}

	/**
	 * Magic Function zum Auslesen von Werten. Die Werte stammen aus dem Array $renderValues.
	 *
	 * @PARAM string $var der Variablenname
	 * 
	 * @RETURN mixed Der Wert der Variable. Bei Verwendung im Parser muss $val ein String sein.
	 */
	public function __get($var)
	{
		if (isset($this->parserValues[$var])) return $this->parserValues[$var];
	}
	
	public function __toString()
	{
		$this->render();
		return $this->getTemplate();
	}
	
	/**
	 * Diese Funktion erzeugt eine Ausgabe für Ajax.
	 *
	 * param String $text Der Text, der per Ajax ausgegeben werden soll.
	 */
	public function addAjaxContent($text)
	{
		$this->app->addAjaxContent($text);
	}
	
	/**
	 * Diese Funktion rendert das Template und gibt es zurück.
	 * 
	 * @return String Das gerenderte Template
	 */
	public function render($reset = false)
	{
		reset($this->parserValues);
		foreach ($this->parserValues AS $var => $val)
		{
			$this->parse($var, strval($val));
		}
		$tpl = $this->getTemplate();
            if ($reset) $this->resetParser();
            return $tpl;
	}
	
	/**
	 * Diese Funktion entfernt alle übrigen Platzhalter aus dem Template
	 */
	public function clearAll()
	{
		//$tmp = $this->getTemplate();
		//$tmp = preg_replace("/\[\[[d]*:(.*?)\]\]/is", "\\1", $tmp);
		//$this->setTemplate($tmp);
		parent::clearAll();
	}
	
	/**
	 * Diese Funktion setzt das Template in den Originalzustand zurück und löscht alle im Controller gesetzten Variablen
	 */
	public function resetParser()
	{
		$this->parserValues = array();
		parent::resetParser();		
	}
	
	private function checkRights() {
		preg_match_all('|<%{([^/].*?):(.*?)}%>|is', $this->getTemplate(), $sub);
		if (count($sub[0]) > 0) {
			foreach($sub[1] AS $key => $name) {
				$start = "<%{".$name.":".$sub[2][$key]."}%>";
				$end = "<%{/".$name."}%>";
				$rights = $sub[2][$key];
				$rights = explode(",", $rights);
				$access = false;
				foreach($rights AS $r) {
					if ($this->acl->checkRight($r)) {
						$access=true;
						break;
					}
				}
				if ($access) {
					$this->remove($start);
					$this->remove($end);
				} else {
					$tpl = $this->getTemplate();
					$tpl = preg_replace("|$start(.*?)$end|is", "", $tpl);
					$this->setTemplate($tpl);
				}
			}
		}
	}
	
	public function buildInlineEditor() {
		$this->initInlineEditor();
		preg_match_all("/\[\[cms:(.*?)\]\]/is", $this->getTemplate(), $sub);

		if (count($sub[0]) > 0) {
			foreach($sub[1] AS $id) {
				$search = "[[cms:".$id."]]";
				$block = new \Block("Content_inline", "index", $id);
				$tpl = str_replace($search, $block->render(), $this->getTemplate());
				$this->setTemplate($tpl);
			}
		}
	}

	/**
	 * Inline Editor initialisieren (JS schreiben)
	 **/
	private function initInlineEditor() {
		$initialized = $this->app->getValue("inline_editor_initialized");
		if (!$initialized) {
            if ($this->app->adminmode()) {
				$this->app->registerCss("
					.cke_editable_inline {
						background:rgba(0,0,0,0.03);
						margin: -1px -3px;
						padding:0 2px;
						border:1px solid #ddd;
					}
					.cke_editable_inline.cke_focus {
						background:transparent;
					}
				", 1);
				$this->app->registerJS("res/js/ckeditor/ckeditor.js");
				$this->app->registerJs("
					CKEDITOR.on( 'instanceCreated', function( event ) {
						var editor = event.editor;
						var element = editor.element;
						if ($(element).attr('contenteditable') == 'true') {
							editor.on( 'configLoaded', function() {
								editor.config.toolbar = editoroptions_inline;
							});
							editor.on( 'blur', function( evt ) {
								var element = evt.editor.element;
								var post = {
									field_id : $(element).attr('id'),
									content : evt.editor.getData()
								}
								$.post(rootdir + 'ajax/blocks/get/content_inline/save', post);
							});
						}
					});
				", true);
			}
			$this->app->setValue("inline_editor_initialized", true);
		}
	}
	
	private function remove($string) {
		$tpl = $this->getTemplate();
		$tpl = str_replace($string, "", $tpl);
		$this->setTemplate($tpl);
	}
	
	/**
	 * Fügt ein Title-Attribut zu einem Element hinzu, damit dieses dann später als Tooltipp angezeigt werden kann
	 *
	 * @param String $id ID des HTML-Elements
	 * @param String $tipp Der Tooltipp, der angezeigt werden soll.
	 **/
	public function tooltipp($id, $tipp) {
		$tpl = $this->getTemplate();
		$tpl = preg_replace("|id='$id'|is", "id='$id' title='$tipp'", $tpl);
		$tpl = preg_replace("|id=\"$id\"|is", "id='$id' title='$tipp'", $tpl);
		$this->setTemplate($tpl);
	}

	public function parseEmojis() {
		$this->render();
		$tpl = $this->getTemplate();
		$client = new \Emojione\Client(new \Emojione\Ruleset());
		$client->imagePathPNG = 'https://bandarena.com/res2/emojione/png/32/'; // defaults to jsdelivr's free CDN
		$tpl = $client->toImage($tpl);
		$this->setTemplate($tpl);
		//echo $tpl;die;
	}
}

?>
