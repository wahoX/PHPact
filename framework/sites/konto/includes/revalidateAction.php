<?php

$guest = $this->acl->checkRight(GUEST);
if (!$guest) $this->app->forward("konto");
$userid = $_SESSION["revalidate_user"];
$this->app->registerCSS("res/css/user.css");

$this->app->addLocation($this->request->url, "Benutzer validieren");

$user     = $this->db->getUserByID($userid);
$username = $user["forename"]." ".$user["surname"];
$password = $user["password"];
$hash     = $user["valid"];
$email    = $user["email"];

if (!empty($hash)) {		
    $sendmail = $this->request->args[0];

    if ($sendmail == "sendmail") {															
        $mail = $this->app->getModule("Email_v2");							
        $mail->addValue("LINK", "http://".$_SERVER["HTTP_HOST"].$this->request->root. "konto/validate/" . $hash);
        $mail->addValue("USERNAME", $username);
        $mail->addValue("MITGLIEDSNUMMER", $userid);
        $mail->send($email, "Ihre Registrierung bei vongruen.de", 1);	

        $this->app->addSuccess("Die E-Mail wurde erneut verschickt.");
        $this->setTemplate("");
    } else {
        $this->app->addHint("Ihr Account ist noch nicht aktiv!");	    
    }
}
else {
    $this->user->login($username, $password, true);			
    $this->app->forward("user/profile");				
}
