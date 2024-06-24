<?php
$return1 = false;
$tmpuser = $this->app->getUser();
$referrer = $this->request->referrer;
if (stristr($referrer, "admin/user_edit")) {
    $id = 0;
    $referrer = explode("?", $referrer);
    $referrer = $referrer[0];
    $referrer = explode("/", $referrer);
    foreach ($referrer AS $r) {
        if (intval($r) == $r) $id = $r;
    }
    if ($id) {
        $x = $this->db->getUserById($id);
        $tmpuser = new \stdClass();
        $tmpuser->id = $x["id"];
    }
}
        
$blacklist = explode("|", "admin|moderator|hitler|nreins");
$fieldname = $this->request->get["extraData"];
if (!$fieldname) $fieldname = $this->request->get["fieldId"];
if ($user != "") $username = $user;
else $username = $this->request->get["fieldValue"];

if (!stristr($username, "@")) {
    if (!preg_match('/^[a-zA-Z0-9\._-]{4,}$/', $username)) {
        if ($ajax) $this->app->addAjaxContent('["'.$fieldname.'",false]');
        return;
    }
    foreach ($blacklist AS $b) {
        if (stristr($username, $b)) {
            if ($ajax) { $this->app->addAjaxContent('["'.$fieldname.'",false]'); }
            return;
        }
    }
}

$check = $this->db->getUserByUsername($username);
// $return : für Ajax-Ausgabe
// $return1 : für return, wenn Funktion so aufgerufen wird.
if ($check === false) { $return = '["'.$fieldname.'",true]'; $return1 = true; }
else {
    if ($check["id"] == $tmpuser->id) { $return = '["'.$fieldname.'",true]'; $return1 = true; }
    else { $return = '["'.$fieldname.'",false]'; $return1 = false; }
}
if ($ajax) $this->app->addAjaxContent($return);
