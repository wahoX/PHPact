<?php


/**
 * Die Validator-Klasse validiert ein Formular
 * 
 * \namespace \
 */
class Validator
{
	
	// Liste der verfügbaren Typen
	private $validTypes = array(
		"email", 	// Muss eine E-Mail Adresse sein
		"number", 	// Muss eine Zahl sein
		"digit", 	// Muss eine Ziffer sein
		"string", 	// Muss ein String sein
		"phone", 	// Muss eine Telefonnummer sein
		"checkbox",	// Checkbox muss ausgewählt sein (nur sinnvoll mit required=true)
		"select",	// Selectbox / Checkboxen / Radio - muss min. 1 gewählt sein (nur sinnvoll mit required=true)
		"regex",	// selbst zu definierender regulärer Ausdruck
		"plz",		// Postleitzahl
		"alphanumeric",	// nur alphanumerische Zeichen und -_
		"any",		// Egal was drin steht - interessant für Pflichtfelder, wo egal ist, was eingegeben ist, hauptsache es wurde was angegeben
		"password",	// Passwort
		"date",		// Datum
		"datum",	// Datum (deutsches Format)
        "steuernr", // Steuernummer
        "ustid",    // Steuernummer
        "time",		// Zeit
        "blz",
        "url"
	);
	
	// In diesem Array werden die zu validierenden Felder gespeichert.
	private $items = array();
	
	// Die vom Nutzer eingegebenen Daten
	private $formdata;
	
	public function construct() {}

	/**
	 * Diese Funktion registriert ein zu validierendes Formularelement.
	 * 
	 * @param String $name Der Name des Formularfeldes
	 * @param String $type Validierungstyp. Muss in $validTypes enthalten sein.
	 * @param boolean $required Is es ein Pflichtfeld?
	 * @param String $regex Regulärer Ausdruck für type=regex
	 *
	 * @throws Exception if type is unknown
	 **/
	public function registerElement($name, $type, $required=false, $regex="")
	{
		if (!in_array($type, $this->validTypes))
		{
			throw new Exception("<b>Validator error</b>: ".$type." is an unknown type!");
		}
		$this->items[$name] = array("name" => $name, "type" => $type, "required" => $required, "regex" => $regex);
	}
	
	/**
	 * Diese Funktion validiert die Formulareingaben
	 * 
	 * @param array $post form data (for example $_POST)
	 * 
	 * @return mixed true, if validation succeeded / array with errors, if not succeeded
	 **/
	public function validate($post)
	{
		$errorFields = array();
		$this->setFormData($post);
		reset($this->items);
		foreach($this->items AS $var => $item)
		{
			$success = $this->validateElement($var);
			if (!$success) 
			{
				array_push($errorFields, $var);
			}
		}
		return $errorFields;
	}
	
	/**
	 * Diese Funktion setzt die vom User eingegebenen Daten.
	 * Muss nicht angegeben werden, da die Funktion validate($post) diese erwartet und dann diese Funktion aufruft.
	 * Macht aber in Kombination mit validateElement($name) Sinn, wenn man nur einen Wert prüfen möchte
	 * 
	 * @param Array $post Die Eingaben, die der User gemacht hat.
	 **/
	public function setFormData($post)
	{
		reset($post);
		foreach($post AS $var => $val) $this->formdata[$var] = $val;
	}
	
	/**
	 * Validiert ein einzelnes Element. Wird von validate($post) aufgerufen.
	 * Man kann diese Funktion auch einzeln aufrufen, wenn man vorher setFormData mit der zu prüfenden Variablen aufgerufen hat.
	 * 
	 * @param String $name Der Name der zu prüfenden Variable
	 * 
	 * @return boolean Ist die Eingabe valide?
	 **/
	public function validateElement($var)
	{
		// (Wert nicht eingegeben   ABER   kein Pflichtfeld               ) alles ok...
		if ($this->formdata[$var] == "" && !$this->items[$var]["required"]) {
			return true;
		}
		
		// Bestimmung der aufzurufenden Funktion
		$callback = "validate_".$this->items[$var]["type"];
			
		//  (Wert nicht eingegeben   ABER   Pflichtfeld                   )ODER Validierung nicht bestanden
		if (($this->formdata[$var] == "" && $this->items[$var]["required"]) || !$this->$callback($this->items[$var]["name"])) {
			return false;
		}
		return true;
	}



	// # # # # # # # # #   Hilfsfunktionen (Validatoren) # # # # # # # # #



	public function validate_email($var) {
		$value = $this->formdata[$var];
		$return = (preg_match('/^[a-zA-Z0-9\.\-\_]+@[a-zA-Z0-9\.\-\_]+\.[a-zA-Z]+$/',$value)) ? true : false;
		return $return;
	}
	
	public function validate_number($var) {
		$value = str_replace(",", ".", $this->formdata[$var]);
		return ($value == floatval($value));
		return preg_match('/^[0-9]+$/', $value);
	}
	
	public function validate_digit($var) {
		$value = $this->formdata[$var];
		return preg_match('/^[0-9]{1}$/', $value);
	}
	
	public function validate_string($var) {
		$value = $this->formdata[$var];
		return preg_match('/^[a-zA-Z0-9_- ]+$/', $value);
		return true;
	}
	
	public function validate_alphanumeric($var) {
		$value = $this->formdata[$var];
		return preg_match('/^[a-zA-Z0-9]{4,}$/', $value);
		return true;
	}
	
	public function validate_phone($var) {
		$search = array(" ", "(", ")", "-", "/", "+");
		$replace = array("", "", "", "", "", "");
		$value = str_replace($search, $replace, $this->formdata[$var]);
		return preg_match('/^[0-9]{3,20}$/', $value);
	}
	
	// fertig? Muss gar nicht mehr geprüft werden, hier gibt es nur Prüfung, ob required und gesetzt (validateElement).
	public function validate_checkbox($var) {
		return $this->formdata[$var];
	}
	
	// fertig? Muss gar nicht mehr geprüft werden, hier gibt es nur Prüfung, ob required und gesetzt (validateElement).
	public function validate_select($var) {
		return $this->formdata[$var];
	}

	// fertig? Muss gar nicht mehr geprüft werden, hier gibt es nur Prüfung, ob required und gesetzt (validateElement).
	public function validate_any($var) {
		return true;
	}
	
	public function validate_regex($var) {
		$value = $this->formdata[$var];
		$regex = $this->items[$var]["regex"];
		return preg_match($regex, $value);
	}
	
	public function validate_plz($var) {
		$value = $this->formdata[$var];
		return preg_match("/^[0-9]{5}$/", $value);
	}

	public function validate_password($var) {
		$value = $this->formdata[$var];
		$numeric = preg_match('/[0-9]/', $value);
		$alpha = preg_match('/[a-zA-Z]/', $value);
		$len = (strlen($value) >= 8);
		return $len;
		#return ($numeric && $alpha && $len);
	}
	
	public function validate_date($var) {
		$value = $this->formdata[$var];
		return preg_match("/^[0-9]{4}[\/\-][0-9]{2}[\/\-][0-9]{2}$/", $value);
	}
	
	public function validate_datum($var) {
		$value = $this->formdata[$var];
		return preg_match("/^[0-9]{2}.[0-9]{2}.[0-9]{4}$/", $value);
	}

	public function validate_datum_date($var) {
		return ($this->validate_date($var) || $this->validate_datum($var));
	}
	
	public function validate_steuernr($var) {
		$value = $this->formdata[$var];
		return preg_match("/^[0-9 \/]{11,13}$/", $value);
	}
	
	public function validate_ustid($var) {
		$value = $this->formdata[$var];
		return preg_match("/^[a-zA-Z]{2}[a-zA-Z0-9]{8,12}$/", $value);
	}

	public function validate_time($var) {
		$value = $this->formdata[$var];
		return preg_match("/^(2[0-3]|[01][0-9]):?([0-5][0-9])$/", $value);
	}

    public function validate_blz($var) {
		$value = $this->formdata[$var];
		return preg_match("/^[0-9]{8}$/", $value);
	}
    
    public function validate_url($var) {
		$value = $this->formdata[$var];
        return (preg_match("/^(http|ftp|https):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&amp;:\/~\+#]*[\w\-\@?^=%&amp;\/~\+#])?$/i", $value)) ? true : false;
    }
}
?>

