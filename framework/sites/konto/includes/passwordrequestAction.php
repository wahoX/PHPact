<?php
$this->app->addLocation($this->request->url, "Passwort vergessen");
$this->app->addTitle("Passwort vergessen?");
$this->app->addDescription("Sie haben ihr Passwort vergessen? Dann können Sie sich hier ein neues anfordern.");
$this->app->addKeywords("NrEins.de, Passwort, Passwort vergessen, Passwort anfordern");

$guest = $this->acl->checkRight(GUEST);
$admin = $this->acl->checkRight(S_ADMIN);
if (!$guest && !$admin) $this->app->forward("konto");

$form = new \Form($this, "requestpassword_form");
$form->registerElement("forgotPassword", "email", true, "");

if ($this->request->post["forgotPassword"] != "")
{
    $form->setFormData($this->request->post);
    $success = $form->run();

    if ($success) 
    {
        $email   = $this->request->post["forgotPassword"];
        $user     = $this->db->getUserByUsername($email);
        if ($user) {
            $hash    = md5(microtime() . $email);
            $url     = $this->request->root;
            $userid   = $user["id"];
            $username = $user["forename"] . " ". $user["surname"];
            $this->db->setHash($hash, $userid, 0, 'konto/resetpassword');

            $mail = $this->app->getModule("Email_v2");
            $mail->addValue("USERNAME", $username);
            $domain = $_SERVER["HTTP_HOST"];
            $mail->addValue("LINK", $domain.$this->request->root. "konto/resetpassword/" . $hash);

            $mail->send($email, "Sie haben ein neues Passwort beantragt", 2);
                $this->app->addAjaxContent("<span class='success'>Die Mail wurde versandt. Bitte prüfen Sie Ihr Postfach.</span>");
                $this->app->AddSuccess("Die Mail wurde versandt. Bitte prüfen Sie Ihr Postfach.");
            if (!$this->request->ajax) $this->app->forward($this->request->referrer);
        } else {
            $this->app->addAjaxContent("<span class='error'>Diese E-Mail Adresse ist nicht registriert!</span>");
            $this->app->AddError("Diese E-Mail Adresse ist nicht registriert!");
        }
    }
    else {
        $this->app->addAjaxContent("<span class='error'>Bitte geben Sie eine gültige E-Mail-Adresse an!</span>");
        $this->app->addError("Bitte geben Sie eine gültige E-Mail-Adresse an!");
    }
}
