<?php
$user = $this->app->getUser();
if ($user->isLoggedIn()) {
    $this->app->forward("konto", 302);
}


$this->app->addLocation($this->request->url, "registrieren");
$this->app->addTitle("Registrieren Sie sich");

$form = new \Form($this, "register_form");
$form->registerElement("gender", "select", true, "");																		//Geschlecht		
$form->registerElement("vorname", "any", true, "Bitte geben Sie Ihren Vornamen an."); 			//Vorrname
$form->registerElement("nachname", "any", true, "Bitte geben Sie Ihren Nachnamen an."); 			//Nachname
$form->registerElement("email", "email", true, "Bitte geben Sie eine gültige E-Mail-Adresse an!","","ajax[ajaxEmailCall]"); 		//Email-Adresse
$form->registerElement("password", "password", true, "Mindestens 8 Zeichen!");												//Passwort
$form->registerElement("password2", "password", true, "Bitte bestätigen Sie Ihr Passwort!", "", "equals[register_password]");	//Passwort bestätigen	
$form->registerElement("newsletter", "checkbox", false);																	//Newsletter abonnieren		
$form->registerElement("agb", "checkbox", true, "Sie müssen die Datenschutzbestimmungen akzeptieren!");								//AGBs akzeptieren		

if ($this->request->method == "POST") {
	$form->setFormData($this->request->post);
	$success = $form->run();

	//Passwörter abgleichen (Wenn beide Felder befüllt sind, aber nicht übereinstimmen = error)
	if(($this->request->post["password"] != "" && $this->request->post["password2"] != "") && (strlen($this->request->post["password"]) < 8)) {
		$success = false;	
		$this->PASSWORD_CLASS = "formerror";
		$this->PASSWORD_MESSAGE = "Das Passwort muss mindestens 8 Stellen haben.";
	}

	//Passwörter abgleichen (Wenn beide Felder befüllt sind, aber nicht übereinstimmen = error)
	if(($this->request->post["password"] != "" && $this->request->post["password2"] != "") && ($this->request->post["password"] != $this->request->post["password2"])) {
		$success = false;	
		$this->PASSWORD2_CLASS = "formerror";				
		$this->PASSWORD2_MESSAGE = "Die eingegebenen Passwörter stimmen nicht überein.";
	}

	$checkmail = $this->checkusernameAction($this->request->post["email"]);
	if (!$checkmail) {
		$success = false;
		$this->EMAIL_MESSAGE = "Diese E-Mail Adresse wird bereits von einem Benutzer verwendet.";
		$this->EMAIL_CLASS = "formerror";
	}
	
	if ($success) {
		$hash       = md5(microtime().$email);              //Hashwert
		$u = new \Datacontainer\User();
		$u->gender = $this->request->post["gender"];
		$u->forename = $this->request->post["vorname"];
		$u->surname = $this->request->post["nachname"];
		$u->email = $this->request->post["email"];
		$u->password = \Utils::encrypt($this->request->post["password"]);
		$u->valid = $hash;
		$id = $u->save();

        $mail = $this->app->getModule("Email_v2");
        $mail->addValue("LINK", "https://".$_SERVER["HTTP_HOST"].$this->request->root. "konto/validate/" . $hash);
        $mail->addValue("DOMAIN", $_SERVER["HTTP_HOST"]);
        $mail->addValue("HOME_LINK", $_SERVER["HTTP_HOST"]);
        $mail->addValue("USERNAME", $u->forename." ".$u->surname);
        $mail->addValue("EMAIL", $u->email);
        $mail->addImage("LOGO", __DIR__."/favicon.png");

        $x = $mail->send($u->email, "Ihre Registrierung", 1);

        $this->app->login($u->email, $u->password, false);
        $this->app->addSuccess("Ihre Anmeldung war erfolgreich. In den nächsten Minuten erhalten Sie eine E-Mail mit den nächsten Schritten.");
        $this->app->forward("");
	}
}
