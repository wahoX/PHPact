<?php
$hash = $this->app->getModule("Hash");
$link = $hash->checkLink();

if (!$link["is_valid"] || $link["url"] != "konto/resetpassword") {
    if (!$link["is_valid"]) $hash->deleteHash();
    $this->app->addError("Der Link ist nicht mehr gültig. Entweder wurde er bereits verwendet, oder er ist zeitlich abgelaufen.");
    $this->app->forward("");
}

$form = new \Form($this, "resetpassword_form");
$form->registerElement("password1", "password", false, "Mindestens 8 Zeichen!");					//Passwort
$form->registerElement("password2", "any", false, "Bitte bestätige dein Passwort!", "", "equals[password1]");		//Passwort bestätigen	
$form->setFormData($this->request->post);
$success = $form->run();

if ($this->request->method == "POST") {
    $password1 = $this->request->post["password1"];
    $password2 = $this->request->post["password2"];
    if (strlen($password1) < 8) {
        $success = false;
        $this->app->addError("Das Passwort muss mindestens 8 Stellen haben.");
    }
    if ($password1 != $password2) {
        $success = false;
        $this->app->addError("Die Passwörter stimmen nicht überein.");
    }
    if ($success) {
        $u = new \Datacontainer\Fw_user($link["user_id"]);
        $u->password = \Utils::encrypt($this->request->post["password1"]);
        $u->save();
        $user = $this->app->getUser();
        $user->login($u->email, $u->password, true);
        $this->app->addSuccess("Das Passwort wurde erfolgreich geändert.");
        $hash->deleteHash();
        $this->app->forward("konto");
    }
}
