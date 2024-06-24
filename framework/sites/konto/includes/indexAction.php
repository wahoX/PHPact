<?php

$user = $this->app->getUser();
if (!$user->isLoggedIn()) {
    $this->app->forward("konto/register", 302);
} else {
	$this->app->forward("konto/profiledit", 302);
}

$this->app->registerOnload("
    $('#timeline').load(rootdir + 'ajax/watchlist/timeline');
");
$this->app->registerCss("res/css/blog.css");

$block = new \Block("Profile", "completion");
$this->COMPLETION = $block->render();
if ($this->acl->checkRight(4) || $this->acl->checkRight(5)) {
    $tplAdminbar = $this->getSubTemplate("ADMINBAR");
    $host = $_SERVER["HTTP_HOST"];
    $tplAdminbar->ADMINLINK = "http://admin.".$host;
    if ($this->app->adminmode()) {
        $tplAdminbar->EDIT_LINK = "admin/setmodus/viewer";
        $tplAdminbar->EDIT_TEXT = "Bearbeiten beenden";
    } else {
        $tplAdminbar->EDIT_LINK = "admin/setmodus/admin";
        $tplAdminbar->EDIT_TEXT = "Inhalte bearbeiten";
    }
    $this->ADMINBAR = $tplAdminbar->render(true);
}

if ($user->avatar) {
    $this->AVATAR = "res/images/user/avatar/small_".$user->avatar;
} else {
    if ($user->gender == 1) $this->AVATAR = "res/images/avatar/men.jpg";
    else $this->AVATAR = "res/images/avatar/woman.jpg";
}
$this->CASHBACK = \Utils::price($user->assets);
$this->VORNAME = $user->forename;
$this->NACHNAME = $user->surname;
$this->ANREDE = ($user->gender==1) ? "Herr" : "Frau";
$this->ADDRESS = $user->address;
$this->EMAIL = $user->email;
$this->USER_ID = $user->id;
