<?php
namespace Blocks;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Profile extends \Controller
{
	private $visible;
	private $fields;

	public function __construct($tpl="", $sub="") {
		parent::__construct($tpl, $sub);
		$this->visible = array(
			0 => "Niemand",
			25 => "Freunde",
			//50 => "Freundesfreunde",
			100 => "Jeder"
		);
                $this->fields = array(
			"firma"         => 100,
			"anrede"        => 100,
			"vorname"       => 100,
			"name"          => 25,
			"strasse"       => 25,
			"hausnr"        => 25,
			"plz"           => 25,
			"ort"           => 25,
			"geburtstag"    => 25,
			"geburtsort"    => 25,
			"geburtsname"   => 25,
			"telefon"       => 25,
			"mobil"         => 25,
			"fax"           => 25,
			"web"           => 100,
			"aim"           => 25,
			"facebook"      => 25,
			"twitter"       => 25,
			"icq"           => 25,
			"skype"         => 25,
			"msn"           => 25,
			"hobbies"       => 25,
			"arbeitgeber"   => 0,
			"strasseag"     => 0,
			"ortag"         => 0,
			"beruf"         => 25,
			"position"      => 25,
			"interessen"    => 25,
			"hobby"         => 25,
			"sport"         => 25,
			"blz"           => 0,
			"kto_inhaber"	=> 0,
			"ktonr"         => 0,
			"bankname"      => 0,
			"bic"           => 0,
			"iban"          => 0,
			"lastschrift"   => 0
		);


	}

    public function indexAction() { }

    public function sidebarAction() {
        $this->WATCHLIST = (new \Block("watchlist", "navi"))->render();
    }

    public function completionAction() {
	require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }

    public function viewAction($view) {
	include(__DIR__."/includes/".__FUNCTION__.".php");
    }

    public function formAction($form) {
	include(__DIR__."/includes/".__FUNCTION__.".php");
    }
    
    public function avatarAction() {
        $this->fill();
        $form = new \Form($this, "username_form");
        $form->registerElement("benutzername", "alphanumeric", true, "Mindestens 4 Zeichen, erlaubte Zeichen: a-z, A-Z, 0-9","","ajax[ajaxUserCall]"); 			//Benutzername
    }

    public function logindatenAction() {
        $this->fill();
        $form = new \Form($this, "password_form");
        $form->registerElement("email", "email", true, "","","ajax[ajaxEmailCall]"); 			//Benutzername
	$form->registerElement("password", "password", false, "Mindestens 8 Zeichen!");					//Passwort
	$form->registerElement("password2", "any", false, "Bitte bestätige dein Passwort!", "", "equals[set_password]");		//Passwort bestätigen	
    }
    
    
    
    private function fill() {
        $user = $this->app->getuser();
        if ($user->avatar) {
                $this->AVATAR = "res/images/user/avatar/".$user->avatar;
        } else {
                if ($user->gender == 1) $this->AVATAR = "res/images/avatar/men.jpg";
                else $this->AVATAR = "res/images/avatar/woman.jpg";
        }
        $this->EMAIL_VALUE = $user->email;
        $this->BENUTZERNAME_VALUE = $user->username;
    }

}

?>
