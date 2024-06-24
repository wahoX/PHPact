<?php
namespace Module;
/**
 * Das Modul Email versendet E-Mails an User.
 */
class Shorturl extends \Module
{
	private $mapping = array(); // Mapping für Base62 umgewandelte Zahl
	private $first = array(); // Umwandlung der 1. Ziffer nach Quersumme
	
	public function __construct() {
		$this->mapping = array(
			'Z','B','K','k','0','8','9','X','I','5',
			'r','Y','g','4','2','T','b','j','D','W',
			'q','l','i','3','O','c','x','a','C','1',
			'p','d','v','w','f','G','7','A','o','s',
			'H','6','F','L','M','e','t','z','u','J',  
			'N','R','n','Q','U','P','E','m','y','V',
			'S','h'
		);
		
		$this->first = array(
			1 => array (1=>2, 2=>3, 3=>4, 4=>5, 5=>6, 6=>7, 7=>8, 8=>9, 9=>1),
			2 => array (1=>9, 2=>1, 3=>2, 4=>3, 5=>4, 6=>5, 7=>6, 8=>7, 9=>8),

			3 => array (1=>3, 2=>4, 3=>5, 4=>6, 5=>7, 6=>8, 7=>9, 8=>1, 9=>2),
			5 => array (1=>8, 2=>9, 3=>1, 4=>2, 5=>3, 6=>4, 7=>5, 8=>6, 9=>7),

			4 => array (1=>5, 2=>6, 3=>7, 4=>8, 5=>9, 6=>1, 7=>2, 8=>3, 9=>4),
			8 => array (1=>6, 2=>7, 3=>8, 4=>9, 5=>1, 6=>2, 7=>3, 8=>4, 9=>5),

			6 => array (1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6, 7=>7, 8=>8, 9=>9),

			7 => array (1=>3, 2=>4, 3=>5, 4=>6, 5=>7, 6=>8, 7=>9, 8=>1, 9=>2),
			9 => array (1=>8, 2=>9, 3=>1, 4=>2, 5=>3, 6=>4, 7=>5, 8=>6, 9=>7),

		);
	}
	
	public function checkRight() {
		return true;
	}


	/**
	 * Holt eine URL anhand der ShortURL.
	 * 
	 * @param String $shorturl Die kurze URL
	 * @param Boolean $count Soll der Aufruf gezählt werden? Nicht gezählt werden soll z.B. bei administrativen Zugriffen.
	 * 
	 * @return String Die URL, zu der weitergeleitet werden soll oder false, wenn es die shorturl nicht gibt.
	 **/
	public function getUrl($shorturl, $count=true) {
		$id = $this->decode_url($shorturl);
		return \DB::getInstance()->shorturlGetUrlByID($id, $count);
	}
	
	
	/**
	 * Speichert eine URL und erzeugt eine ShortURL.
	 * 
	 * @param String $url Die zu speichernde URL
	 * 
	 * @return String Die ShortURL, unter der es abgelegt wurde bzw. false, wenn das keine URL ist.
	 **/
	public function saveUrl($url) {
		// URL prüfen und ggf. korrigieren
		$info = \Utils::get_url_info($url, false);
		// keine gültige URL? Dann Abbruch.
		if ($info["http_code"] == 0) return false;
		// korrigierte URL aus der Info holen
		$url = $info["url"];
		$id = \DB::getInstance()->shorturlSaveUrl($url);
		$short = $this->encode_url($id);
		return $short;
	}



	####### Hilfs-Funktionen zum de- und encodieren der ID #######

	/**
	 * Codiert eine Zahl $var in einen String mit der Basis $base
	 * 
	 * @param Int $var ID der URL
	 * @param Int $base die Länge des Mapping-Arrays 
	 * 
	 * @return String die codierte URL
	 **/ 
	public function encode_url($id, $mask = true)	{
		if ($mask) $id = $this->mask_id($id);
		$base = sizeof($this->mapping);
		if ($id < $base)	{ // see if it's time to return
			return $this->mapping[$id];
		}
		else {
			$x = $id % $base;
			return $this->encode_url(floor($id/$base), false) . $this->mapping[$x];	// continue
		}
	}


	/**
	 * Decodiert den übergebenen String und errechnet die ID.
	 * 
	 * @param String $url die angefragte URL
	 * 
	 * @return Integer ID der URL
	 **/
	public function decode_url($url) {
		$num = 0;
		$base = sizeof($this->mapping);
		
		//There's just no chance encoded URL will ever be too long so if 
		//we get something like that - somebody is messing with us trying
		//to eat up CPU cycles on decoding or cause some other kind of overflow. 
		if (strlen($url)>10) return 0;
		
		$seq = str_split ($url);    
		if (!is_array($seq) || !(sizeof($seq)>0)) return 0;
		
		$seq = array_reverse(str_split ($url));

		$m = array_flip($this->mapping);
		
		$i = 0;
		foreach ($seq as $c) {
		  if (isset($m[$c])) {
			$val = (int)$m[$c];
			$num += ($val * pow($base, $i));
			$i++;
		  }
		}
		return $this->mask_id($num);
	}
	
	private function mask_id($id) {
		$qs = $this->quersumme($id);
		$id = str_split($id."");
		$return = $this->first[$qs][$id[0]];
		unset($id[0]);
		$id = array_reverse($id);
		$return .= implode("", $id);
		return $return;
	}
	
	private function quersumme($in){
		if(is_numeric($in)){
			$arr = str_split((int)$in);
			$r = 0;
			foreach($arr as $v) $r += $v;
			if ($r > 9) return $this->quersumme($r);
			return $r;
		} else return false;
	}
	
}
