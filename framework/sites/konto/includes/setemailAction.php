<?php

$hash = $this->app->getModule("Hash");
$link = $hash->checkLink();

if (!$link["is_valid"] || $link["url"] != "konto/setemail") {
    if (!$link["is_valid"]) $hash->deleteHash();
    $this->app->addError("Der Link ist nicht mehr gültig. Entweder wurde er bereits verwendet, oder er ist zeitlich abgelaufen.");
    $this->app->forward("konto/profiledit");
}

$check = $this->checkusernameAction($link["data"]->email, false);

if ($check) {
    $u = new \Datacontainer\Fw_user($link["user_id"]);
    $u->email = $link["data"]->email;
    $u->save();
    $this->app->addSuccess("Die E-Mail Adresse wurde erfolgreich geändert.");
    $user = $this->app->getUser();
    $user->login($u->email, $u->password, true);
} else {
    $this->app->addError("Die E-Mail Adresse konnte nicht geändert werden, da sie bereits von einem Benutzer verwendet wird.");
}
$hash->deleteHash();
$this->app->forward("konto/profiledit");
