<?php
$user = $this->app->getUser();
if (!$user->isLoggedIn()) {
    $this->app->forward("konto/register", 302);
}

$this->VORNAME = $user->forename;
$this->NACHNAME = $user->surname;
$this->POINTS = intval($user->points);

$this->app->addLocation($this->request->url, "Profil bearbeiten");
$this->app->addTitle("Profil bearbeiten");

$this->app->registerJs("res/js/jquery/jquery.form.js");

// Sinnloses Formular, damit JS und CSS für ValidationEngine geladen werden
$tmp = new \Form($this, "noform");

//Blöcke für Views erstellen
$forms = array("persoenlich");
foreach($forms AS $f) {
	$b = new \Block("Profile", "form", $f);
	$placeholder = strtoupper($f);
	$this->$placeholder = $b->render();
}

$this->LOGINDATEN = (new \Block("profile", "logindaten"))->render();
