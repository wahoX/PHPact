<?php
$user = $this->app->getUser();
$forms = array("persoenlich", "kontakt", "beruf", "interessen", "bank");
$what = $view;
if (!in_array($what, $forms)) {
	return;
}
$tpl = $this->getSubtemplate(strtoupper($what));
$extradata = $user->extradata;

if (!isset($extradata->vorname)) $extradata->vorname = array("value" => $user->forename, "visibility" => $this->fields["vorname"]);
if (!isset($extradata->name)) $extradata->name = array("value" => $user->surname, "visibility" => $this->fields["name"]);
if (!isset($extradata->geburtstag)) {
	if ($user->birthday != "" && $user->birthday != "0000-00-00") {
		$g = new \DateTime($user->birthday." 00:00:00");
		$geburtstag = $g->format("d.m.Y");
	} else $geburtstag = "";
	$extradata->geburtstag = array("value" => $geburtstag, "visibility" => $this->fields["geburtstag"]);
}
$geschlecht = ($user->gender == 1) ? "Herr" : "Frau";
if (!isset($extradata->anrede)) {
	$geschlecht = ($user->gender == 1) ? "Herr" : "Frau";
	$extradata->anrede = array("value" => $geschlecht, "visibility" => $this->fields["anrede"]);
}

foreach($extradata AS $k => &$v) {
	$v["visibility_string"] = $this->visible[$v["visibility"]];
}

$tpl->EXTRADATA = (array) $extradata;
$this->VIEW = $tpl->render();
