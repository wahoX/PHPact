<?php
$user = $this->app->getUser();
if (!$user->isLoggedIn()) die;
$username = $this->request->post["benutzername"];
$check = $this->checkusernameAction($username, false);
if ($username == $user->username) {
    $this->app->addHint("Sie haben keinen neuen Benutzernamen angegeben. Es wurde nichts geändert.");
} else if ($check) {
    $u = new \Datacontainer\Fw_user($user->id);
    $u->username = $username;
    $u->save();
    $user->update();
    $this->app->addSuccess("Der Benutzername wurde erfolgreich geändert.");
} else {
    $this->app->addError("Der Benutzername ist unzulässig oder bereits vergeben.");
}
$b = new \Block("Profile", "avatar");
$this->app->addAjaxContent($b->render());
$this->app->forward("konto/profiledit");
