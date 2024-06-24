<?php

/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Module
{
	
	public $db; // Instanz der Datenbank-Klasse (DB_Portal)
	public $app; // Instanz der Applikation
	public $request; // Instanz des Request-Objektes
	public $acl; // Instanz der ACL
	public $user; // der User
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->db = DB::getInstance();
		$this->app = Application::getInstance();
		$this->request = Request::getInstance();
		$this->acl = ACL::getInstance();
		$this->user = $this->app->getUser();
	}

    /**
     * Diese Funktion lädt ein anderes Model in $this->db
     * 
     * @param string $classname Name des Models
     */
   	public function loadModel($classname) {
		if (
			file_exists($_SERVER["DOCUMENT_ROOT"].$this->request->root."models/".$classname.".class.php")
			|| file_exists(FRAMEWORK_DIR . $this->request->root."models/".$classname.".class.php")
		) {
			require_once("models/".$classname.".class.php");
			$classname = "\\Models\\".$classname;
			$this->db = $classname::getInstance();
		} 
	}

	public function onRender() {}
	
	/**
	 * Abfrage, ob User eingeloggt ist.
	 * 
	 * @retrurn Boolean true wenn eingeloggt, false wenn nicht
	 **/
	public function isLoggedIn()
	{
		return $this->user->isLoggedIn();
	}
	
	/**
	 * Abfrage, ob User das gast-Recht hat (das hat er, wenn er nicht eingeloggt ist - u.U. haben best. User dieses auch.
	 * 
	 * @retrurn Boolean true wenn Gast, false wenn nicht
	 **/
	public function isGuest()
	{
		return $this->acl->checkRight(GUEST);
	}
}

?>
