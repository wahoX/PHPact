<?php
namespace Module;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Cms extends \Module
{
	public function checkRight() {
		return true;
	}

	public function getUrl($url="") {
		if ($url=="") {
			$url = \Request::getInstance()->args[0];
		}
		return $url;
	}

	public function getEditLink($url)
	{
		return "cms/edit/".$url;
	}
	
	public function getItem() {
		$url = $this->getUrl();
		return \DB::getInstance()->getCmsSite($url);
	}

}
