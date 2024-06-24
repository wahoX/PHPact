<?php

$this->loadModel("User");
$this->app->addLocation($this->request->url, "Benutzer validieren");
if (!$this->acl->checkRight(GUEST)) $this->app->forward("konto");
$this->app->registerCSS("res/css/user.css");
$hash = $this->request->args[0];
$user = $this->db->getUserByHash($hash);

if ($user) {	
    $this->app->getUser()->login($user["email"], $user["password"], true);
    $_SESSION["User"] = $this->app->getUser();
    $u = new \Datacontainer\User($user["id"]);
    $u->valid="";
    $u->save();
    $this->app->addSuccess("Herzlichen Glückwunsch - Ihr Account ist nun aktiv!");
    $this->app->forward("");
}
else {
    $this->app->addError("Beim Anmeldevorgang ist etwas schief gelaufen!");
    $this->app->forward("");
}
