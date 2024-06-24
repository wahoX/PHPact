<?php

/**
 * \namespace \
 */

/**
 * Die User-Klasse enthält alle Informationen des eingeloggten Users.
 */
class User
{
    // In diesem Array werden die Userdaten gespeichert.
    private $data = [];
    private $extradata = [];
    private $bands = [];

    // Eingeloggt?
    private $logged_in = false;

    // Benutzergruppen, in denen der User Mitglied ist
    private $groups = array();

    public function construct() {}

    public function __sleep()
    {
        return array('data', 'logged_in', 'groups', 'bands');
    }

    // Magischer Setter und Getter
    public function __set($var, $val) { $this->data[$var] = $val; }
    public function __get($var) { return (isset($this->data[$var])) ? $this->data[$var] : null; }

    /**
     * Diese Funktion prüft das Login und befüllt bei Erfolg das Objekt mit den Daten des Users
     *
     * @param String $username Benutzername des Users
     * @param String $password Passwort des Users
     * @param Booean $cookie Stammen die Daten aus Cookie? Wenn ja, ist das PW schon MD5-verschlüsselt.
     *
     * @return boolean War das Login erfolgreich?
     **/
    public function login($username, $password, $cookie=false)
    {
        $user = DB::getInstance()->getUser($username, $password, $cookie);
        if ($user) {
            foreach ($user AS $key => $val)
            {
                // Nur die Werte setzen wo der Key keine Zahl ist (mit Zahl sind IDs durch db->fetch_array - und die brauchen wir nicht)
                if (!is_int($key)) $this->$key = $val;
            }
            // Holen der Gruppen, in denen der User Mitglied ist.
            $this->groups = DB::getInstance()->getUserGroups($user["id"]);
            $this->logged_in = true;

            //last_login und last_ip setzen
            DB::getInstance()->setLogin($this->id);
			$this->initData();

            return true;
        }
        return false;
    }

    public function getData() {
      $return = $this->data;
      $return["extradata"] = $this->extradata;
      return $return;
    }

    public function facebook_connect()
    {
        $redirect_to = $_SESSION["fb_login_referer"];
        $app = \Application::getInstance();
        $fb = \FB::getInstance()->getFbObject();
        $response = $fb->get('/me?fields=first_name,middle_name,last_name,email', $_SESSION["fb_access_token"]);
        $user = $response->getGraphUser();
        if (!$user) {
            $app->addError("Die Verknüpfung mit Facebook ist fehlgeschlagen.");
            $app->forward("");
        }

        if ($this->isLoggedIn()) {
            $u = new \Datacontainer\User($this->id);
            $u->facebook_id = $user["id"];
            $u->save();
            $this->update();
            $this->getFbAvatar($user["id"]);
            $app->addSuccess("Du hast dein Profil erfolgreich mit Facebook verknüpft. Zukünftig kannst du dich auch einfach über Facebook einloggen.");
            $app->forward("konto/profiledit");
        }

        $u = \DB::getInstance()->getUserByFacebookId($user["id"]);
        if ($u) {
            $this->login($u["username"], $u["password"], true);
            $this->getFbAvatar($user["id"]);
            if (stristr($u["username"], ":not_set:") || stristr($u["password"], ":not_set:") || stristr($u["email"], ":not_set:")) {
                $app->forward("konto/incomplete");
            }
            $app->addSuccess("Du hast dich erfolgreich eingeloggt.");
            $app->forward($redirect_to);
        }

        if ($user["email"] != "") {
            $u = \DB::getInstance()->getUserByEmail($user["email"]);
            if ($u) {
                $this->login($u["username"], $u["password"], true);
                $this->getFbAvatar($user["id"]);
                if (stristr($u["username"], ":not_set:") || stristr($u["password"], ":not_set:") || stristr($u["email"], ":not_set:")) {
                    $app->forward("konto/incomplete");
                }
                $app->addSuccess("Du hast dich erfolgreich eingeloggt.");
                $app->forward($redirect_to);
            }
        }

        $u = new \Datacontainer\User();
        $u->username = ":not_set:".$user["id"];
        $u->email = ($user["email"] != "") ? $user["email"] : ":not_set:".$user["id"]."@facebook.com";
        $u->password = ":not_set:";
        $u->forename = $user["first_name"];
        $u->facebook_id = $user["id"];
        $u->notification = 3;
        if (trim($user["middle_name"])) $u->forename .= " ".$user["middle_name"];
        $u->surname = $user["last_name"];
		$now = new \DateTime();
		if ($now->format("Y") < 2020) $u->vip_until = "2019-12-31";
        $u->save();

        $this->login($u->username, $u->password, true);
        $this->getFbAvatar($user["id"]);

        $app->forward("konto/incomplete");

    }

    private function getFbAvatar($fbid) {
        if ($this->avatar != "") return;

        $hash = md5(microtime(true));

        $tmpfile = FRAMEWORK_DIR . "res/uploads/tmp/".$fbid.".jpg";
        $image = "https://graph.facebook.com/".$fbid."/picture?type=large";
        $img = file_get_contents($image);
        file_put_contents($tmpfile, $img);

        $i = \Application::getInstance()->getModule("image");
        $finalname = FRAMEWORK_DIR . "res/uploads/user/";
        if (!file_exists($finalname)) mkdir($finalname);
        $finalname .= $this->id."/";
        if (!file_exists($finalname)) mkdir($finalname);
        $finalname .= "avatar/";
        if (!file_exists($finalname)) mkdir($finalname);
        $finalname .= $hash;
        $i->resizeImage($tmpfile, 400, $finalname.".jpg");
        $u = new \Datacontainer\User($this->id);
        $u->avatar = "/res2/uploads/user/".$this->id."/avatar/".$hash.".jpg";
        $u->save();
    }

    /**
     * Diese Funktion aktualisiert die Userdaten aus der Datenbank
     * Sinnvoll, wenn der User bearbeitet wurde (Punkte bekommen, Profildaten geändert, ...)
     **/
    public function update()
    {
        $user = DB::getInstance()->getUserByID($this->id);
        if ($user) {
            foreach ($user AS $key => $val)
            {
                // Nur die Werte setzetm wo der Key keine Zahl ist (mit Zahl sind IDs durch db->fetch_array - und die brauchen wir nicht)
                if (!is_int($key)) $this->$key = $val;
            }
			$this->initData();
        }
    }

    /**
     * Diese Funktion prüft, ob der Benutzer eingeloggt ist.
     *
     * @RETURN boolean Eingeloggt oder nicht?
     **/
    public function isLoggedIn()
    {
        return $this->logged_in;
    }

    public function isVip() {
        if (!$this->isLoggedIn()) return false;
        return true;
        $now = new \DateTime();
        $vip_until = new \DateTime($this->vip_until." 23:59:59");
        return ($now < $vip_until) ? true : false;
    }

    public function getGroups()
    {
        if (!$this->logged_in) return DB::getInstance()->getUserGroups(1);
        return $this->groups;
    }

	private function initData() {
        $this->extradata = DB::getInstance()->getUserExtraData($this->id);

        $_SESSION["User"] = $this;
        $_SESSION["User_id"] = $this->id;
        
	}
}
?>
