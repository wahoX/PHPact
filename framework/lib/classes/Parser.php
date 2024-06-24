<?php
/*
 *	Version 1.3
 *  + Parser kann auch mit einem Template-String initialisiert werden (muss keine Datei sein)
 *  + Konstruktor kann sofort mit einem Tag initialisiert werden
 *
 *  Version 1.2
 *  + clearAll wird auch bei showTemplate aufgerufen
 *  - clearAll und fillAll regulre Ausrdcke angepasst
 *
 *	History:
 *	=> Version 1.1: + public function resetParser() - setzt das Template wieder auf den Ursprung (ungeparst)
 *	=> Version 1.0: Original by Mr.X
 */

/**
 * \namespace \
 */


class Parser {

	private $template = "";
	private $templateOriginal = "";
	private $blankchar ="&nbsp;";


	/**
	 * Initialisiert Parser entweder mit Template-Datei oder leer. Ist der übergebene Werte KEINE Datei, wird der Wert als Template-Text gesetzt.
	 *
	 * @param tplFilename Einzulesende Datei
	 */
	public function __construct($tplFilename="", $tplName="") {

		// durch dieses Codestück können auch html Datei lokal überschrieben werden!
		if (!empty($tplFilename)) {
			$userdir = dirname($_SERVER["SCRIPT_FILENAME"])."/"; // Verzeichnis in dem lokale index.php liegt
			$globaldir = FRAMEWORK_DIR;
			$tplFilename = str_replace($globaldir, "", $tplFilename); // entferne falls vorhanden
			if (file_exists($userdir.$tplFilename)) { $tplFilename = $userdir.$tplFilename; }
			else if (file_exists($globaldir.$tplFilename)) { $tplFilename = $globaldir.$tplFilename; }
		}


		if (!empty($tplFilename) && is_file($tplFilename)) {
			$this->template = implode('', file($tplFilename));
			$this->templateOriginal = $this->template;

			// liest nur das Tag $tplName ein, falls übergeben
			if (!empty($tplName)) {
				$this->setTemplate($this->parseTag($tplName));
			}
		}
		else if (empty($tplFilename) && empty($tplName)) {
			$this->setTemplate("");
		}
		else if (empty($tplFilename) && !empty($tplName)) {
			$this->setTemplate("");
			Application::getInstance()->addException(new Exception("ERROR: Es wurde kein Template übergeben."));
		}
		else {
			$this->setTemplate("");
			Application::getInstance()->addException(new Exception("ERROR: Template konnte nicht geladen werden: <strong>".$tplFilename."</strong>"));
		}

	}

	public function __sleep() {
		return array("template", "templateOriginal");
	}

	public function isEmpty() {
		return empty($this->template);
	}


	public function getTemplate() {
		return $this->template;
	}

	public function setTemplate($tpl, $rewriteOriginal=true) {
		$this->template = $tpl;
		if ($rewriteOriginal) { $this->templateOriginal = $this->template; }
	}


	public function replace($search, $replace)
	{
		$this->template = preg_replace("|".$search."|is", $replace, $this->template);
	}


	/**
	 * Parst Platzhalter im Stil: <%name%>
	 */
	public function parse($var, $content, $fill=false) {
		$rpl_str = "|<%".$var."%>|";

		if ($content=="" && $fill) {
			$content= $this->blankchar;
		}

		$this->template = preg_replace ($rpl_str, $content, $this->template);
	}

	/**
	 * Parst alle Zeichen.
	 */
	public function parseString($var, $content) {
		$rpl_str = "|".$var."|";
		$this->template = preg_replace($rpl_str, $content, $this->template);
	}


	/**
	 * Entfernt alle ungeparsten Platzhalter sowie alle Kommentare und leeren HTML-Attribute (auch SubTemplates)
	 */
	public function clearAll() {
		// alle Tag-Bereiche (<%[TAG]%> ... <%[/TAG]%>) in normale Einzel-Tags (<%[TAG]%>) umwandeln
		$tpl = $this->template;
		preg_match_all('|<%\[\/(.*?)\]%>|is', $tpl, $sub);
		foreach($sub[1] AS $tag) {
		    $start = strpos($tpl, "<%[".$tag."]%>");
		    $end = strpos($tpl, "<%[/".$tag."]%>");
		    $tpl1 = substr($tpl, 0, $start);
		    $tpl2 = substr($tpl, ($end + 7 + strlen($tag)), strlen($tpl));
		    $tpl = $tpl1.$tpl2;
		}
		$this->template = $tpl;

		// Alt... Bringt Probleme, wenn Subtemplate zu groß ist.
		// $this->template = preg_replace("|<%\[.*?<%\[\/|is", "<%[", $this->template);

		$this->parse(".*?","");
		//$this->template = preg_replace("|<!--(.*?)-->|is", "", $this->template); // keine Kommentare raus parsen, sonst gehen keine IE Skripte mehr
		$this->template = preg_replace("| & |is", " &amp; ", $this->template);
		$this->template = preg_replace("| class=\"\"|is", "", $this->template);
		$this->template = preg_replace("| style=\"\"|is", "", $this->template);
		$this->template = preg_replace("| id=\"\"|is", "", $this->template);
	}

	public function fillAll() {
		$this->parse(".*?","&nbsp;");
	}

	public function showTemplate() {
		$this->clearAll();
		echo $this->template;
	}

	public function resetParser() {
		$this->template = $this->templateOriginal;
	}

	/**
	 * Parst das Template nach Tags im Stil: <%[tagname]%> und <%[/tagname]%>.
	 * Gibt den Inhalt zwischen ffnenden und schlieendem Tag zurck.
	 */
	public function parseTag($tag) {
		$tag= "<%".$tag."%>";

		if (strpos($this->template, $tag) === FALSE) return "";

		$tagContent="";
		$tagClosed = str_replace ("[", "[/", $tag);

		$pos1 = strpos($this->template,$tag);
		$pos2 = strpos($this->template,$tagClosed);

		$parsePos1 = $pos1+strlen($tag);
		$parsePos2 = $pos2-$parsePos1;

		$tagContent = substr($this->template,$parsePos1,$parsePos2);

		return $tagContent;
	}
}

?>
