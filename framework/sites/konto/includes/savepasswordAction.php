<?php

// ToDo: Bei Ändrung der E-Mail Adresse muss die neue Adresse bestätigt werden!

$user = $this->app->getUser();
if (!$user->isLoggedIn()) die;

$email = $this->request->post["email"];
$check = $this->checkusernameAction($email, false);
if ($email == $user->email) {
} else if ($check) {
    $hash = md5(microtime());
    $now = new \DateTime();
    $data = new \stdClass();
    $data->email = $email;
    $now->add(new \DateInterval("P2D"));
    $h = new \Datacontainer\Hash();
    $h->hash = $hash;
    $h->user_id = $user->id;
    $h->url = "konto/setemail";
    $h->valid_until = $now->format("Y-m-d H:i:s");
    $h->data = json_encode($data);
    $h->save();

    $username = $user->forename . " ". $user->surname;
    $domain = $_SERVER["HTTP_HOST"];

    $mail = $this->app->getModule("Email_v2");
    $mail->ignore_sandbox();
    $mail->addImage(FRAMEWORK_DIR . "res/images/mail/user_header.jpg", "HEADER_IMAGE");
    $mail->addImage(FRAMEWORK_DIR . "res/images/mail/footer.jpg", "FOOTER_IMAGE");
    $mail->addValue("USERNAME", $username);
    $mail->addValue("LINK", $domain.$this->request->root. "konto/setemail/" . $hash);
    $mail->addValue("MITGLIEDSNUMMER", $user->id);
    $mail->addValue("EMAIL", $email);
    $mail->send($email, "Aktualisierung Ihrer E-Mail Adresse", 14);
    
    $this->app->addHint("
        Wir haben Ihre neue E-Mail Adresse hinterlegt.<br />
        Um die Änderung zu übernehmen, müssen Sie die E-Mail Adresse noch bestätigen. Wir haben Ihnen dafür eine E-Mail mit einem Bestätigungslink geschickt. Diese hat eine Gültigkeitsdauer von 2 Tagen.
    ");
} else {
    $this->app->addError("Die E-Mail Adresse wird bereits von einem anderen Benutzer verwendet.");
}

if ($this->request->post["password"] != "") {
    $old = \Utils::encrypt($this->request->post["old_password"]);
    if ($old != $user->password) {
        $this->app->addError("Das angegebene Passwort ist falsch.");
    } else if ($this->request->post["password"] != $this->request->post["password2"]) {
        $this->app->addError("Die Passwörter stimmen nicht überein.");
    } else if (strlen($this->request->post["password"]) < 8) {
        $this->app->addError("Das Passwort muss mindestens 8 Stellen haben.");
    } else {
        $u = new \Datacontainer\User($user->id);
        $u->password = \Utils::encrypt($this->request->post["password"]);
        $u->save();
        $user->update();
        $this->app->addSuccess("Das Passwort wurde erfolgreich geändert.");
    }
}

$this->app->forward("konto/profiledit");
