<?php

$user = $this->app->getUser();
if (!$user->isLoggedIn()) $this->app->forward("konto/register");

if ($this->request->args[0] == "getcoupon") {
	if ($user->points < 20) {
		$this->app->addError("Sie haben nicht ausreichend Bonuspunkte, um einen Gutschein anzufordern.");
	} else {
		$code = $this->generate_code();
		$this->app->addSuccess("Ihr Gutschein wurde erzeugt.<br />Der Gutscheincode lautet: <b>".$code."</b><br />Wir haben Ihnen zudem eine E-Mail mit dem Gutscheincode geschickt.");
		$c = new \Datacontainer\Coupons();
		$c->title = "5 Euro Wertgutschein";
		$c->code = $code;
		$c->price = -5;
		$c->onetime = 1;
		$c->ignore_mbw = 1;
		$c->valid_from = "2000-01-01";
		$c->valid_until = "2200-01-01";
		$c->save();
		
        $username = $user->forename . " ". $user->surname;
        $mail = $this->app->getModule("Email_v2");
        $mail->addValue("USERNAME", $username);
        $mail->addValue("COUPON", $code);
        $mail->send($user->email, "Ihr VonGruen-Gutscheincode", 15);
        
        $this->app->addUserPoints(-20, "Anforderung Gutscheincode");
		
	}
	$this->app->forward("konto/points");
}

$this->POINTS = $user->points;
$this->VORNAME = $user->forename;
$this->NACHNAME = $user->surname;

$template = ($user->points >= 20) ? "GET_COUPON" : "NOT_ENOUGH";
$tplAction = $this->getSubtemplate($template);
$this->ACTION = $tplAction->render();
