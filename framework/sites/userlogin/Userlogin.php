<?php
namespace Sites;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Userlogin extends \Controller
{
	public function indexAction() {
		$user = $this->app->getUser();
		if ($user->isLoggedIn()) $this->app->forward("");
	}
}
