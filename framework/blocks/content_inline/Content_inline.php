<?php
namespace Blocks;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Content_inline extends \Controller
{
	/**
	 * Diese Funktion erzeugt das Div mit dem Inline-Editor
	 * 
	 * @param String $field_id Die ID des Feldes
	 **/
	public function indexAction($field_id)
	{
		// ID der Seite ermitteln
		$site_id = $this->app->getSite();
		// Suche in DB
		$tmp = $this->query("content_inline")->where("field_id = '".\Utils::escape($field_id)."' AND site_id = ".$site_id)->asArray()->run();
		
		if (isset($tmp[0])) {
		} else {
			// Wenn kein Datensatz, dann trotzdem Wert erzeugen, leerer Content
			$tmp[0] = array("content" => "");
		}
		
		// SubTemplate laden
		$subtemplate = ($this->app->adminmode()) ? "ADMINMODE" : "DEFAULT";
		$tpl = $this->getSubtemplate($subtemplate);
		
		$tpl->ID = $field_id;
		$tpl->TEXT = $tmp[0]["content"];
		if (trim($tmp[0]["content"]) == "") $tpl->PLACEHOLDER = "Dies ist ein leeres Inhalts-Element.";
		
		$this->BLOCK = $tpl->render();
	}
	
	/**
	 * Diese Funktion speichert die Daten aus dem Inline-Editor
	 * 
	 * @param String $field_id Die ID des Feldes
	 **/
	public function saveAction() {
		$this->app->disableRender();
		$this->app->addAjaxContent(print_r($this->request->post, 1));
		// ID der Seite ermitteln
		$site_id = $this->app->getSite();
		// Field_id aus post hoen (abgesichert)
		$field_id = str_replace("inlineeditor_", "", $this->request->post["field_id"]);
		// Datensatz suchen
		$tmp = $this->query("content_inline")->where("field_id = '".\Utils::escape($field_id)."' AND site_id = ".$site_id)->asArray()->run();
		// Datensatz gefunden? Dann Datacontainer laden
		if (isset($tmp[0])) {
			$content = new \Datacontainer\Content_inline($tmp[0]["id"]);
		// Nicht gefunden? Dann neuen Datacontainer erzeugen und Site_id und Field_id (nicht abgesichert!!!) setzen
		} else {
			$content = new \Datacontainer\Content_inline();
			$content->site_id = $site_id;
			$content->field_id = $field_id;
		}
		// Inhalt schreiben
		$content->content = $this->request->post["content"];
		// Speichern
		$content->save();
	}
}

?>
