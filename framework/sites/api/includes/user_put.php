<?php
/**
 * 
Funktion: user
Methode: PUT

Parameter:

- user - als Json-Objekt. Nur Stamm- und Extradaten. Adressen werden ignoriert.
         Ansonsten gleicher Aufbau wie das User-Objekt, das bei user GET zurück gegeben wird.

Es ist ausschließlich die id Pflicht. Ohne Angabe der ID kann nicht gespeichert werden.
Alle anderen Werte sind optional. Wenn sie angegeben werden, werden sie aber validiert.
Schlägt eine Validierung fehl, wird nichts gespeichert.

Im Fehlerfall bekommst du 2 Werte:

    Error (Text)
    Errorcode (Zahl).

Errorcodes:

    1: Das JSON-Objekt konnte nicht gelesen werden.
    2: Der User hat keine ID.
    3: Sie haben keine Berechtigung, diesen User zu bearbeiten.
    4: Die angegebene E-Mail Adresse ist ungültig.
    5: Die angegebene E-Mail Adresse wird bereits von einem User verwendet.
    6: Das angegebene Geschlecht ist ungültig. Gültige Werte sind: '1' bzw. 'male' und  '2' bzw. 'female'.
    7: Das angegebene Geburtsdatum ist ungültig.


Im Erfolgsfall bekommst du

    Success=1


Beispielaufruf:

        $user = new \StdClass();
        $user->id = 100102503;
        $user->forename = "Seb";
        $user->surname = "Kubi";
        $user->extradata = new \StdClass();
        $user->extradata->aim = "";
        $user->extradata->beruf = "Pixelschieber";
        $data = array("user"=>json_encode($user));
        $hmac = new \Hmac($key, $secret, "PUT", $data);
        $result = $hmac->request("user");

**/


// JSON parsen
$user = json_decode($this->request->put["user"]);
if (!is_object($user)) {
	$this->result->Error = "Das JSON-Objekt konnte nicht gelesen werden.";
	$this->result->Errorcode = 1;
	return;
}

// ID holen
$id = intval($user->id);
if ($id < 1) {
	$this->result->Error = "Der User hat keine ID.";
	$this->result->Errorcode = 2;
	return;
}

// prüfen, ob API-User den User bearbeiten darf
$tmpuser = $this->db->getUserById($id);
if ($tmpuser["created_by"] != $this->auth["user"]) {
	$this->result->Error = "Sie haben keine Berechtigung, diesen User zu bearbeiten.";
	$this->result->Errorcode = 3;
	return;
}

// E-Mail Adresse prüfen
if ($user->email != "") {
	if (!\Utils::validateEmail($user->email)) {
		$this->result->Error = "Die angegebene E-Mail Adresse ist ungültig.";
		$this->result->Errorcode = 4;
		return;
	} else {
		require_once(FRAMEWORK_DIR . "sites/user/Konto.php");
		$userobj = new \Sites\Konto();
		$check = ($userobj->checkusernameAction($user->email, false)) ? "1" : "0";
		if (!$check) {
			$this->result->Error = "Die angegebene E-Mail Adresse wird bereits von einem User verwendet.";
			$this->result->Errorcode = 5;
			return;
		}
		unset($userobj);
	}
}

// Geschlecht prüfen
if ($user->gender != "") {
	$gender = $user->gender;
	if (strtolower($gender) == "male") $gender = 1;
	if (strtolower($gender) == "female") $gender = 2;
	$gender = intval($gender);
	if ($gender < 1 || $gender > 2) {
		$this->result->Error = "Das angegebene Geschlecht ist ungültig. Gültige Werte sind: '1' bzw. 'male' und  '2' bzw. 'female'.";
		$this->result->Errorcode = 6;
		return;
	}
	$user->gender = $gender;
	$user->extradata->anrede = $gender;
}

// Geschlecht prüfen
if ($user->birthday != "") {
	$birthday = $user->birthday;
	$now = new \DateTime();
	$bd = new \DateTime($birthday);
	$year = $bd->formay("Y");
	if ($year <= 1900 || $year >= $now->format("Y")) {
		$this->result->Error = "Das angegebene Geburtsdatum ist ungültig.";
		$this->result->Errorcode = 7;
		return;
	}
	$user->birthday = $bd->format("Y-m-d");
}

$tmpuser = new \Datacontainer\User($user->id);
$data = array("forename", "surname", "birthday", "gender", "email");
$stammdaten = array("id" => $id);
foreach ($data AS $d) {
	if (isset($user->$d)) {
		$tmpuser->$d = $user->$d;
	}
}
$tmpuser->save();

// Stammdaten speichern
if (count($stammdaten) > 1) $this->db->saveUser($stammdaten);

foreach ($user->extradata AS $k => $v) {
	
	$this->db->saveUserExtraData($k, $v, false, $id);
}

$this->result->Success = 1;


?>
