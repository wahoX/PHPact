<?php
/**
 * \namespace \Sites
 */
namespace Blocks;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
use \Controller as Controller;

class Location extends \Controller
{
	
	public function indexAction()
	{
		$l = $this->app->getLocation();
		if (!is_array($l)) $l = array();
		$tplLocation = $this->getSubtemplate("LOCATION");
		$count = 0;
		foreach ($l AS $url => $title) {
			$tplLocation->URL = $url;
			$tplLocation->TITLE = $title;
			if ($count++ > 0) {
                            $this->LOCATIONS .= '<span style="font-size: 8px;" class="glyphicon glyphicon-chevron-right"></span>';
                            $tplLocation->CHILD = 'itemprop="child"';
                        }
			$this->LOCATIONS .= $tplLocation->render();
			$tplLocation->resetParser();
		}
		for ($i=1; $i<=count($l); $i++) $this->LOCATIONS .= "</div>";
	}

}
	
?>
