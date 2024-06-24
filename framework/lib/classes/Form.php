<?php
/*
 *	Formular-Klasse zur Auswertung und Validierung von Formularen
 *
 *	Eingegebene Daten werden mit setFormData($post) an die Klasse übergeben.
 *	Zu validierende Felder werden mit registerElement(...) registriert.
 *	Die Ausführung wird mit run() gestartet.
 *
 *	Verwendbare Platzhalter im Formular bei einzelnen Formularfeldern:
 *	{NAME}_VALUE - der Wert, der voreingetragen werden soll
 *	{NAME}_CLASS - An diese Stelle soll im Fehlerfall die CSS-Klasse "formerror" eingetragen werden
 *	{NAME}_MESSAGE - an dieser Stelle wird eine eventuelle Fehlermeldung ausgegeben
 *
 *	Beispiel:
 *
 *	<span class="<%PLZ_CLASS%>">Plz: <input type="text" name="plz" value="<%PLZ_VALUE%>" /> <%PLZ_MESSAGE%></span>
 */

/**
 * \namespace \
 */
class Form
{
	// In diesem Array werden die zu validierenden Felder gespeichert.
	private $items = array();
	
	// Die vom Nutzer eingegebenen Daten
	private $formdata;

	private $controller;
	
	private $validator;
	
	private $is_error = false;
	
	private $has_run = false; // Wird auf true gesetzt, wenn Validiert wurde (teilweise wird das doppelt gemacht)
	
	/**
	 * Formularklasse benötigt den Controller, der das Formular enthält, um dann Platzhalter ersetzen zu können.
	 *
	 * @param Controller $c Der Controller
	 * @param Sring $formid ID des Formulares. Wenn angegeben, wird der jQuery-Validator instanziiert.
	 **/
	public function __construct(&$c, $formid = "")
	{
		$this->controller = &$c;
		$this->validator = new Validator();
		if ($formid) {
			Application::getInstance()->registerJS("res/js/jquery/jquery.validationEngine-de.js");
			Application::getInstance()->registerJS("res/js/jquery/jquery.validationEngine.js");
			Application::getInstance()->registerCSS("res/css/validationEngine.jquery.css");
			Application::getInstance()->registerOnLoad("$('#".$formid."').validationEngine('attach');");
		}
	}
	
	/**
	 * Diese Funktion registriert ein zu validierendes Formularelement.
	 * 
	 * @param String $name Der Name des Formularfeldes
	 * @param String $type Validierungstyp. Muss in Validator->validTypes enthalten sein.
	 * @param boolean $required Is es ein Pflichtfeld?
	 * @param String $message Meldung, die im Fehlerfall ausgegeben werden soll.
	 * @param String $regex Regulärer Ausdruck für type=regex#
	 * @param String $misc Sonstige Angaben für den JS-Validator. z.B. equals[password1]
	 **/
	public function registerElement($name, $type, $required=false, $message="", $regex="", $misc="")
	{
		try {
			$this->validator->registerElement($name, $type, $required, $regex);
		} catch (Exception $e) {
			Application::getInstance()->addException($e);
		}
		$this->setValue($name, "message", $message);
		$this->setValue($name, "type", $type);
		$options = array();
		if ($required) $options[] = "required";
		switch ($type)
		{
			case "email" : $options[] = "custom[email]"; break;
			case "number" : $options[] = "custom[number]"; break;
			case "digit" : $options[] = "custom[number],max[9]"; break;
			case "phone" : $options[] = "custom[phone]"; break;
			case "alphanumeric" : $options[] = "custom[onlyLetterNumber]"; break;
			case "password" : $options[] = "minSize[8]"; break;
			case "plz" : $options[] = "custom[zip]"; break;
			case "date" : $options[] = "custom[date]"; break;
			case "datum" : $options[] = "custom[datum]"; break;
			case "steuernr" : $options[] = "custom[steuernr]"; break;
			case "ustid" : $options[] = "custom[ustid]"; break;
			case "blz" : $options[] = "custom[blz]"; break;
			case "time" : $options[] = "custom[time]"; break;
			case "url" : $options[] = "custom[url]"; break;
		}
		if ($misc != "") $options[] = $misc;

		$placeholder = strtoupper($name)."_VALIDATE";
		$this->controller->$placeholder = "validate[".implode(",",$options)."] ";
	}

	/**
	 * Hilfsfunktion, um einen Wert beim FormularElement zu speichern (Wert, CSS-Klasse, Fehlermeldung)
	 *
	 * @param String $name Name des Items
	 * @param String $var Variable, in die geschrieben werden soll
	 * @param String $val Wert, der gespeichert werden soll
	 **/
	private function setValue($item, $var, $val)
	{
		if (isset($this->items[$item]) && is_array($this->items[$item])) $this->items[$item][$var] = $val;
		else $this->items[$item] = array($var => $val);
	}

	/**
	 * Diese Funktion setzt die vom User eingegebenen Daten.
	 * 
	 * @param Array $post Die Eingaben, die der User gemacht hat.
	 **/
	public function setFormData($post)
	{
		$this->formdata = $post;
//		$this->validator->setFormData($post);
		reset ($post);
		foreach ($post AS $key => $val)
		{
			$this->setValue($key, "value", $val);
		}
	}

	private function validate()
	{
		reset ($this->items);
		foreach ($this->items AS $item => $val)
		{
			if (!$this->formdata[$item]) $this->formdata[$item] = $val["value"];
		}

		$response = $this->validator->validate($this->formdata);
		if (count($response) > 0) $this->is_error = true;
		reset ($this->items);
		foreach ($this->items AS $item => $val)
		{
			if (!in_array($item, $response)) {
				if (isset($this->items[$item]["message"])) unset($this->items[$item]["message"]);
			}
			else {
				$this->setValue($item, "class", "formerror");
			}
		}
	}
	
	/**
	 * Diese Funktion validiert das Formular und ersetzt anschließend die Platzhalter im Template.
	 *
	 * @return Boolean true, wenn kein Validierungsfehler, sonst false
	 **/
	public function run()
	{
		if (!$this->formdata) {
			$this->setFormData(\Request::getInstance()->post);
		}
		$this->validate();
		reset ($this->items);
		foreach ($this->items AS $name => $values)
		{
            #$values["value"] = str_replace($search, $replace, $values["value"]);
            #if ($name == "tarif_array") {print_r($values);die;continue;}
			$value = strtoupper($name)."_VALUE";
			$class = strtoupper($name)."_CLASS";
			$message = strtoupper($name)."_MESSAGE";
			$checked = strtoupper($name)."_CHECKED";
            $checked2 = strtoupper($name)."_".$values["value"]."_CHECKED";
            $selected = strtoupper($name)."_".$values["value"]."_SELECTED";

			$this->controller->$value = (isset($values["value"])) ? $values["value"] : "";
			$this->controller->$class = (isset($values["class"])) ? $values["class"] : "";
			$this->controller->$message = (isset($values["message"])) ? $values["message"] : "";
			if (isset($values["type"]) &&$values["type"] == "checkbox") {
				$this->controller->$checked = ($values["value"] > 0) ? "checked='checked'" : "";
			}
            // [ darf nicht im Platzhalter vorhanden sein, sonst funktioniert RegExp beim Ersetzen nicht.
            if (isset($values["value"]) && !is_array($values["value"]) && !stristr($values["value"], "[")) {
                $this->controller->$selected = "selected='selected'";
                $this->controller->$checked2 = "checked='checked'";
            }
		}
		if (!$this->has_run && $this->is_error) \Application::getInstance()->addError("Die Daten konnten aufgrund fehlerhafter Eingaben nicht übernommen werden. Bitte prüfen Sie Ihre Angaben.<br /><strong>Hinweis:</strong> Fehlerhafte Felder sind rot hinterlegt.");
		$this->has_run = true;
		return !$this->is_error;
	}
	
}

?>
