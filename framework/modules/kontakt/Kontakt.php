<?php
namespace Module;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Kontakt extends \Module
{
	public function checkRight() {
		return true;
	}

	public function getUrl() {
		return "form/contact";
	}

	public function getEditLink()
	{
		return false;
	}

}
