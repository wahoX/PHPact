<?php
/**
 * 
Funktion: user
Methode: POST

Parameter:

    email (es findet eine erneute Prüfung statt
    password (min. 8 Stellen)
    gender (mögliche Werte: 1 bzw. male und 2 bzw female)
    newsletter (1, wenn kunde einen Newsletter möchte)
    forename (Vorname)
    surname (Nachname)

Bis auf Newsletter sind alle Angaben Pflicht.

Im Fehlerfall bekommst du 2 Werte:

    Error (Text)
    Errorcode (Zahl).

Errorcodes:

    1: Die angegebene E-Mail Adresse ist ungültig.
    2: Die angegebene E-Mail Adresse wird bereits von einem User verwendet.
    3: Das Passwort ist zu kurz. Es muss mindestens 8 Stellen haben.
    4: Das angegebene Geschlecht ist ungültig. Gültige Werte sind: '1' bzw. 'male' und  '2' bzw. 'female'.
    5: Es wurde kein Vorname angegeben.
    6: Es wurde kein Nachname angegeben.


Im Erfolgsfall bekommst du

    Success=1
    Id=[ID des neuen Users]


Wenn Id=0, dann ist ein Fehler beim Speichern aufgetreten.


Beispielaufruf:

$data = array("email" => "test@domain.tld", "password" => "test1234", "gender" => "male", "newsletter" => "1", "forename" => "Muster", "surname" => "Mann");
$hmac = new \Hmac($key, $secret, "POST", $data);
$result = $hmac->request("user");

**/

$email = trim($this->request->post["email"]);
if (!\Utils::validateEmail($email)) {
    $this->result->email = "0";
    $this->result->Error = "Die angegebene E-Mail Adresse ist ungültig.";
    $this->result->Errorcode = "1";
    return;
} else {
    require_once(FRAMEWORK_DIR . "sites/user/Konto.php");
    $user = new \Sites\Konto();
    $check = ($user->checkusernameAction($email, false)) ? "1" : "0";
    if (!$check) {
        $this->result->Error = "Die angegebene E-Mail Adresse wird bereits von einem User verwendet.";
        $this->result->Errorcode = "2";
        return;
    }
}

$password = trim($this->request->post["password"]);
if (strlen($password) < 8) {
    $this->result->Error = "Das Passwort ist zu kurz. Es muss mindestens 8 Stellen haben.";
    $this->result->Errorcode = 3;
    return;
}

$gender = trim($this->request->post["gender"]);
if (strtolower($gender) == "male") $gender = 1;
if (strtolower($gender) == "female") $gender = 2;
$gender = intval($gender);
if ($gender < 1 || $gender > 2) {
    $this->result->Error = "Das angegebene Geschlecht ist ungültig. Gültige Werte sind: '1' bzw. 'male' und  '2' bzw. 'female'.";
    $this->result->Errorcode = 4;
    return;
}

$forename = trim($this->request->post["forename"]);
if (!$forename) {
    $this->result->Error = "Es wurde kein Vorname angegeben.";
    $this->result->Errorcode = 5;
    return;
}
$surname = trim($this->request->post["surname"]);
if (!$surname) {
    $this->result->Error = "Es wurde kein Nachname angegeben.";
    $this->result->Errorcode = 6;
    return;
}

$newsletter = intval(trim($this->request->post["newsletter"]));
if ($newsletter != 1) $newsletter = 0;

$hash = md5(microtime().$email);

$user = new \Datacontainer\User();
$user->password = \Utils::encrypt($password);
$user->gender = $gender;
$user->email = $email;
$user->valid = $hash;
$user->forename = $forename;
$user->surname = $surname;
$new_id = $user->save();


$username   = ($gender == '1') ? "Herr " : "Frau ";
$username  .= $forename . " " . $surname;

$mail = $this->app->getModule("Email_v2");
$mail->addImage(FRAMEWORK_DIR . "res/images/mail/user_header.jpg", "HEADER_IMAGE");
$mail->addImage(FRAMEWORK_DIR . "res/images/mail/footer.jpg", "FOOTER_IMAGE");
$mail->addValue("LINK", "http://".$_SERVER["HTTP_HOST"].$this->request->root. "user/validateuser/" . $hash);
$mail->addValue("USERNAME", $username);
$mail->addValue("HOME_LINK", $_SERVER["HTTP_HOST"]);
$mail->addValue("MITGLIEDSNUMMER", $new_id);
$mail->addValue("EMAIL", $email);

$mail->send($email, "Ihre Registrierung bei NrEins.de", 1);

$this->result->Success = "1";
$this->result->Id = $new_id;

?>
