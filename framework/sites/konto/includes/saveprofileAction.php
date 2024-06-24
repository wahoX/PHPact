<?php
$this->app->disableRender();
$user_id = $this->app->getUser()->id;
if (!$user_id) die;

$u = new \Datacontainer\Fw_user($user_id);

foreach ($this->request->post AS $k => $v) {
    $this->db->saveUserExtraData($k, $v);
    if ($k == "vorname" && trim($v != "")) $u->forename = $v;
    if ($k == "name" && trim($v != "")) $u->surname = $v;
    if ($k == "geburtstag" && trim($v != "")) {
        $g = new \DateTime($v." 00:00:00");
        $u->birthday = $g->format("Y-m-d");
    }
    if ($k == "anrede" && trim($v != "")) {
        $u->gender = $v;
	}
}
$u->save();

$what = $this->request->args[0];
$this->app->getUser()->update();
$b = new \Block("Profile", "form", $what);
$this->app->addAjaxContent($b->render()."<div class='clearfix'></div>&nbsp;<div class='alert alert-success'>Die Daten wurden gespeichert.</div>");
