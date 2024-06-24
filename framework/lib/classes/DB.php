<?php

/**
 * Allgemeine Datenbank-Klasse, die globale Datenbank-Abfagen realisiert
 * Singleton-Klasse
 *
 * Erreichbare Eltern Variablen und Methoden:
 * parent::public function __construct($host = "", $user = "", $passwd = "", $dbname = "")
 * parent::public function getLink()
 * parent::public function query($sql)
 * parent::public function setDatabase($dbname)
 * parent::public function isConnected()
 * parent::public function UTF8()
 * parent::public function ISO()
 */
class DB extends MySQL_i {

    private static $instance = NULL;
    private $navi = false;
    private $active = false;
    private $shopnavi = false;
    private $shopactive = false;
    private $siteinfo = false;
    private $shopprofile = false;
    private $offererlist = false;

    protected function __construct() {
        parent::__construct(DB_Host, DB_User, DB_Pass, DB_DB);
    }

    public static function getInstance() {
        if (self::$instance == NULL) {
            self::$instance = new DB();
        }
        return self::$instance;
    }

    
    /**
     * Diese Funktion gibt einen Benutzer anhand der Logindaten zurück.
     *
     * @param String $username Der Benutzername
     * @param String $password Das Passwort
     *
     * @return Array Array mit den Daten des Benutzers
     * */
    public function getUserByHash($hash) {
        $hash = Utils::secure($hash);
        if ($hash == "") return false;
        $sql = "SELECT * FROM user WHERE valid = '$hash'";
        $result = $this->query($sql);
        if ($return = $this->fetch_assoc($result))
            return $return;
        return false;
    }

    /**
     * Diese Funktion gibt einen Benutzer anhand der Logindaten zurück.
     *
     * @param String $username Der Benutzername
     * @param String $password Das Passwort
     * @param String $email Email-Adresse
     * @param String $ip IP Adresse des Clients		 
     *
     * */
    public function addUser($username, $password, $gender, $email, $advertiser, $hash, $reference, $forename = null, $surname = null, $gsk = null, $gsk2 = null, $vp_number = 0, $birthday = null, $creator=0) {
        $username = \Utils::escape($username);
        $password = md5($password);
        $gender = intval($gender);
        $email = \Utils::escape($email);
        $advertiser = intval($advertiser);
        if ($advertiser > 2 && $advertiser < 100000000)
            $advertiser += 100000000;
        $hash = \Utils::escape($hash);
        $reference = \Utils::escape($reference);
        $forename = \Utils::escape($forename);
        $surname = \Utils::escape($surname);
        $gsk = \Utils::escape($gsk);
        $gsk2 = \Utils::escape($gsk2);
        $creator = intval($creator);
        
        if (substr($birthday, 2, 1) == "." && substr($birthday, 5, 1) == ".") {
            $birthday = substr($birthday, 6, 4) . "-" . substr($birthday, 3, 2) . "-" . substr($birthday, 0, 2);
        }
        
        $this->query("INSERT INTO user
						  	(username, password, gender, email, advertiser, valid, reference, forename, surname, birthday, gsk_nr, gsk2_nr, vp_nummer, created_by)
						  VALUES
						  	('" . $username . "', '" . $password . "', '" . $gender . "', '" . $email . "', '" . $advertiser . "', '" . $hash . "', '" . $reference . "', '" . $forename . "', '" . $surname . "', '" . $birthday . "', '" . $gsk . "', '" . $gsk2 . "', '" . $vp_number . "', '" . $creator . "')");
        return $this->getLastInsertID();
    }

    /**
     * Diese Funktion setzt den Usernamen eines Benutzers
     *
     * @param String $username Der Benutzername
     * @param Int ID ID des Benutzers	 
     *
     * */
    public function updateUserName($username, $ID) {
        $username = Utils::escape($username);
        $ID = intval($ID);

        $this->query("UPDATE user SET username = '" . $username . "' WHERE ID = " . $ID);
    }

    /**
     * Diese Funktion gibt einen Benutzer anhand der Logindaten zurück.
     *
     * @param String $username Der Benutzername
     * @param String $password Das Passwort
     * @param String $email Email-Adresse
     * @param String $ip IP Adresse des Clients		 
     *
     * */
    public function setLogin($userid) {
        $this->query("UPDATE user SET last_login = NOW(), login_count = login_count+1 WHERE id = " . intval($userid));
    }

    /**
     * Diese Funktion setzt geänderte Benutzerdaten.
     *
     * @param String $userID Der User ID
     * @param String $email E-Mail Adresse des Users
     * @param String $password Das Passwort
     * @param Boolean $updatepw Wenn true, dann soll das PW geändert werden
     * @param String $avatar Name des Avatar-Bildes
     * @param Boolean $deleteValid Wenn true, wird der Valid-String beim User gelöscht (E-Mail validiert)
     * */
    public function setUser($userID, $email, $password, $updatepw = false, $avatar, $deleteValid = false) {
        //$username = trim(strtolower(Utils::secure($username)));
        //if (!$cookie) $password = md5($password);
        //else $password = $this->escape_string($password);

        $pw = ($updatepw) ? ", password    = '" . md5($password) . "'" : "";
        $valid = ($deleteValid) ? ", valid = ''" : "";
        $email = Utils::secure($email);

        $sql = "
				UPDATE user
				SET email       = '" . $email . "'
				" . $pw . "
				" . $valid . "
				WHERE id = '" . $userID . "'
			";
        $this->disableCache();
        $result = $this->query($sql);
        $rows = $this->affected_rows();
        return $rows;
    }

    /**
     *  Diese Funktion gibt die  User mit dem Recht 9 zurück.
     * 
     */
    public function getUserByRight($right) {
        $SQLString = "SELECT `id`, `email`, `username`, `forename`, `surname`, `points`, `points_collected`, `avatar`, `advertiser`, `manager`, `gsk_nr`, `gsk2_nr`,
                `user_id`, `right_id` FROM user LEFT JOIN user_right ON id=user_id WHERE right_id='$right' ORDER BY `id`";
        $result = $this->query($SQLString);
        while ($zeile = $this->fetch_assoc($result)) {
            $return[] = $zeile;
        }
        return $return;
    }

    /**
     * Diese Funktion gibt einen Benutzer anhand der Logindaten zurück.
     *
     * @param String $username Der Benutzername
     * @param String $password Das Passwort
     *
     * @return Array Array mit den Daten des Benutzers
     * */
    public function getUser($username, $password, $cookie = false) {
        $username = trim(strtolower(Utils::secure($username)));
        if ($username == "") return false;
        
        $row = (stristr($username, "@")) ? "email" : "username";

        $sql = "SELECT * FROM user WHERE LOWER(" . $row . ") = '$username' AND deleted=0";
        $result = $this->query($sql);
        if ($return = $this->fetch_assoc($result)) {
            if (!$cookie) {
                if (!\Utils::password_verify($password, $return["password"])) return false;
            } else {
                $tmp_password = $password;
                if ($return["password"] != $tmp_password) return false;
            }
            return $return;
        }
        return false;
    }


    /**
     * Diese Funktion gibt einen Benutzer anhand der Logindaten zurück.
     *
     * @param String $username Der Benutzername
     * @param String $password Das Passwort
     *
     * @return Array Array mit den Daten des Benutzers
     * */
    public function getFacebookUser($facebook_id, $email) {
        $facebook_id = Utils::secure($facebook_id);
        $email = Utils::secure($email);

        $sql = "SELECT * FROM user WHERE (facebook_id = '" . $facebook_id . "' OR email LIKE '" . $email . "') AND deleted=0";
        $result = $this->query($sql);
        if ($return = $this->fetch_assoc($result))
            return $return;
        return false;
    }

    /**
     * Diese Funktion gibt einen Benutzer anhand der Logindaten zurück.
     *
     * @param String $username Der Benutzername
     * @param String $password Das Passwort
     *
     * @return Array Array mit den Daten des Benutzers
     * */
    public function saveFacebookUser($facebook_data, $user_id = 0) {
        $user_id = intval($user_id);
        $facebook_id = Utils::secure($facebook_data["id"]);
        $birthday = Utils::secure($facebook_data["birthday"]);
        $firstname = Utils::secure($facebook_data["first_name"]);
        $lastname = Utils::secure($facebook_data["last_name"]);
        $email = Utils::secure($facebook_data["email"]);
        $gender = Utils::secure($facebook_data["gender"]);
        switch ($gender) {
            case "male" : $gender = 1;
                break;
            case "female" : $gender = 2;
                break;
            default: $gender = 0;
        }

        $birthday = explode("/", $birthday);
        $birthday = $birthday[2] . "-" . $birthday[0] . "-" . $birthday[1];

        if ($user_id > 2) {
            $result = $this->query("SELECT * FROM user WHERE id = '" . $user_id . "'");
            $user = $this->fetch_assoc($result);
            if ($user["birthday"] && $user["birthday"] != '0000-00-00')
                $birthday = $user["birthday"];
            if ($user["forename"])
                $firstname = $user["forename"];
            if ($user["surname"])
                $firstname = $user["surname"];
            if ($user["gender"])
                $gender = $user["gender"];
            if ($user["email"])
                $email = $user["email"];

            $this->query("
				    UPDATE user SET
						facebook_id = '$facebook_id',
						birthday = '$birthday',
						forename = '$firstname',
						surname = '$lastname',
						gender = '$gender',
						email = '$email'
					WHERE id = '" . $user_id . "'
				");
        } else {

            // ID des Werbers ermitteln, wenn vorhanden
            if (isset($_SESSION["advertiser"])) {
                if ($_SESSION["advertiser"] > 2 && $_SESSION["advertiser"] < 100000000)
                    $_SESSION["advertiser"] += 100000000;

                if (!is_numeric($_SESSION["advertiser"])) {
                    $tmp = $this->getUserByUsername($_SESSION["advertiser"]);
                } else
                    $tmp = array("id" => $_SESSION["advertiser"]);
            }
            if ($tmp)
                $advertiser = $tmp["id"];
            else
                $advertiser = 0;

            $this->query("
				    INSERT INTO user
						(facebook_id, birthday, forename, surname, gender, email, advertiser)
				    VALUES
						('$facebook_id', '$birthday', '$firstname', '$lastname', '$gender', '$email', '$advertiser')
				");
            return $this->getLastInsertID();
        }
    }

    /**
     * Diese Funktion gibt einen Benutzer anhand der ID zurück.
     *
     * @param Integer $id ID des Users
     *
     * @return Array Array mit den Daten des Benutzers
     * */
    public function getUserById($id, $deleted = false) {
        $delete = (!$deleted) ? "AND deleted=0" : "";
        $id = intval($id);
        $sql = "SELECT * FROM user WHERE id = $id $delete";
        $result = $this->query($sql);
        if ($return = $this->fetch_assoc($result))
            return $return;
        return false;
    }

    /**
     * Diese Funktion gibt einen Benutzer anhand der E-Mail zurück.
     *
     * @param String $email E-Mail des Users
     *
     * @return Array Array mit den Daten des Benutzers
     * */
    public function getUserByEmail($email) {
        if (trim($email) == "")
            return 0;

        $email = strtolower(\Utils::escape($email));
        $sql = "SELECT * FROM user WHERE LOWER(email) = '" . $email . "' AND deleted=0";
        $result = $this->query($sql);
        if ($return = $this->fetch_assoc($result))
            return $return;
        return false;
    }


    /**
     * Diese Funktion gibt einen Benutzer anhand seines Benutzernamens zurück.
     *
     * @param String $username Der Benutzername
     *
     * @return Array Array mit den Daten des Benutzers
     * */
    public function getUserByUsername($username) {
        $username = trim(strtolower(Utils::secure($username)));
        $sql = "SELECT * FROM user WHERE (LOWER(username) = '$username' OR LOWER(email) = '$username')";
        $result = $this->query($sql);
        if ($return = $this->fetch_assoc($result))
            return $return;
        return false;
    }

    /**
     * Diese Funktion gibt die Gruppen zurück, in denen der User Mitglied ist.
     *
     * @param int $uid Die User-ID des Benutzers
     *
     * @return Array Array mit den Gruppen, in denen der User Mitglied ist.
     * */
    public function getUserGroups($uid) {
        $return = array();
        $uid = intval($uid);
        $site = \Application::getInstance()->getSite();
        $sql = "
				SELECT `user_right`.right_id, `right`.name, `right`.description
				FROM `user_right`
				LEFT JOIN `right` ON user_right.right_id = `right`.id
				WHERE 
					user_right.user_id = '" . $uid . "' AND 
					(user_right.site_id = '$site' OR user_right.site_id = 1)
				ORDER BY user_right.right_id
			";
        $result = $this->query($sql);
        while ($id = $this->fetch_assoc($result)) {
            $return[$id["right_id"]] = $id;
        }
        return $return;
    }


    /**
     * Speichert Extra-Daten eines Users
     * 
     * @param String $key Der Schlüssel
     * @param String $val Der Wert
     * @param Integer $visibility Die Sichtbarkeit - je höher, desto mehr Leute können das sehen. 0 = nur für den User selbst
     * */
    public function saveUserExtraData($key, $val, $visibility=false, $user_id = 0) {
        $key = \Utils::escape($key);
        $val = \Utils::escape($val);

        $user_id = intval($user_id);
        if ($user_id < 2) {
            $user = \Application::getInstance()->getUser();
            $user_id = intval($user->id);
        }
        
        if ($key == "anrede") {
            if ($val == "1") $val = "Herr";
            else if ($val == "2") $val = "Frau";
        }
        
        if ($user_id < 2)
            return false;
        if ($visibility !== false) {
            $visibility = intval($visibility);
            $setvisibility = "`visibility` = '$visibility',";
        } else $visibility = 0;

        $query = "INSERT INTO user_extradata (user_id, `key`, `value`, `created`, `changed`, `visibility`) 
                        VALUES ($user_id, '$key', '$val', NOW(), NOW(), $visibility)
                      ON DUPLICATE KEY UPDATE `value` = '$val', $setvisibility `changed` = NOW();";
        $this->query($query);
        if ($this->errno() != 0) {
            var_dump('Fehler: ' . $this->error() . ', SQL: ' . $query);
            die();
        }
    }

    /**
     * Liest die Extra-Daten eines Users
     * 
     * @param String $userID ID des Users, dessen Daten geholt werden sollen
     * @param String $visibility Nur Daten, die minimal $visibility als Sichtbarkeit haben.
     * 
     * @return Array Daten des Users
     * */
    public function getUserExtraData($userID, $visibility = 0) {
        $userID = intval($userID);
        $visibility = intval($visibility);
        $sql = "SELECT * FROM user_extradata WHERE user_id = " . $userID . " AND visibility >= " . $visibility;
        $result = $this->query($sql);
        $return = array();
        while ($tmp = $this->fetch_assoc($result)) {
            $return[$tmp["key"]] = array(
                "value" => $tmp["value"],
                "visibility" => $tmp["visibility"],
                "created" => $tmp["created"],
                "changed" => $tmp["changed"]
            );
        }
        return $return;
    }


    /**
     * Diese Funktion gibt alle registrierten Aktionen zurück, die mit den angegebenen Rechten ausgeführt werden dürfen.
     *
     * @param Array $usergroups Benutzergruppen, die abgefragt werden sollen.
     *
     * @return Array Array mit Aktionen, die ausgeführt werden dürfen (In diesem Array ist Key == Value)
     * */
    public function getActions($usergroups) {
        $return = array();
        if (is_array($usergroups) && count($usergroups) > 0) {
            $groups = array_keys($usergroups);
            $groups = implode(", ", $groups);
        }
        else
            $groups = "'" . intval($usergroups) . "'";

        $sql = "SELECT action_id FROM action_right WHERE right_id IN (" . $groups . ")";
        $result = $this->query($sql);
        while ($id = $this->fetch_assoc($result)) {
            $return[$id["action_id"]] = $id["action_id"];
        }
        return $return;
    }

    /**
     * Diese Funktion liefert alle verfügbaren Aktionen zurück
     *
     * @return array Assoziatives Array: Key: ID der Aktion, Value: Array mit Name, Beschreibung und allen Gruppen, die Zugriff haben
     * */
    public function getAllActions() {
        $return = array();
        $sql = "
				SELECT actions . * , action_right.right_id, `right`.name AS right_name, `right`.description AS right_description
				FROM `portal_actions`
				LEFT JOIN action_right ON actions.id = action_right.action_id
				LEFT JOIN `right` ON `right`.id = action_`right`.right_id
				ORDER BY actions.id, action_right.right_id
			";
        $result = $this->query($sql);
        while ($tmp = $this->fetch_assoc($result)) {
            $rights = array($tmp["right_id"] => array("id" => $tmp["right_id"], "name" => $tmp["right_name"], "description" => $tmp["right_description"]));
            if ($tmp["right_id"] < 1)
                $rights = array();
            if (is_array($return[$tmp["id"]])) {
                $array = $return[$tmp["id"]]["rights"];
                $rights = array_merge($array, $rights);
            }
            $return[$tmp["id"]] = array(
                "name" => $tmp["name"],
                "description" => $tmp["description"],
                "rights" => $rights
            );
        }
        return $return;
    }

    /**
     * Diese Funktion liefert alle verfügbaren Rechte-Gruppen zurück
     *
     * @return array Assoziatives Array: Key: ID der Gruppe, Value: Array mit Name, Beschreibung und allen verfügbaren Aktionen
     * */
    public function getAllRights() {
        $return = array();
        $sql = "
				SELECT `right`.* , action_right.action_id, actions.name AS action_name, actions.description AS action_description
				FROM `right`
				LEFT JOIN action_right ON `right`.id = action_right.right_id
				LEFT JOIN actions ON actions.id = action_right.action_id
				ORDER BY `right`.id, action_right.action_id
			";
        $result = $this->query($sql);
        while ($tmp = $this->fetch_assoc($result)) {
            $actions = array($tmp["action_id"] => array("id" => $tmp["action_id"], "name" => $tmp["action_name"], "description" => $tmp["action_description"]));
            if ($tmp["action_id"] < 1)
                $actions = array();
            if (is_array($return[$tmp["id"]])) {
                $array = $return[$tmp["id"]]["actions"];
                $actions = array_merge($array, $actions);
            }
            $return[$tmp["id"]] = array(
                "name" => $tmp["name"],
                "description" => $tmp["description"],
                "actions" => $actions
            );
        }
        return $return;
    }

    public function setSites($id, $post, $adress, $extra) {
        $server = \UTILS::escape($post['servername']);
        $alias = \UTILS::escape($post['alias']);
        $redirect = \UTILS::escape($post['redirect']);
        $dir = \UTILS::escape($post['dir']);
        $as = intval($post["branche"]);
        $conf_id = intval($post["conf_id"]);

        if ($conf_id == 0) {
            if ($dir == "")
                $dir = "layout1";
            $SQLString = "INSERT INTO apacheconfig (`servername`, `alias`, `redirect`, `dir`, `owner`, `branchen_id`) 
                            VALUES ('$server','$alias','$redirect','$dir','$id','$as');";
            $this->query($SQLString);

            $site_id = $this->getLastInsertID();
            $SQLString = "INSERT INTO menu (`site_id`, `name`, `module`, `pos`, `level`, `url`, `visible`, `formular`)
                    VALUES ('$site_id','Startseite','cms','1','0','Startseite','1','0')";
            $this->query($SQLString);

            $SQLString = "INSERT INTO menu (`site_id`, `name`, `module`, `pos`, `level`, `url`, `visible`, `formular`)
                    VALUES ('$site_id','Angebote','cms','2','0','Angebote','1','0')";
            $this->query($SQLString);

            $SQLString = "INSERT INTO menu (`site_id`, `name`, `module`, `pos`, `level`, `url`, `visible`, `formular`)
                    VALUES ('$site_id','Neuigkeiten','cms','3','0','Neuigkeiten','1','0')";
            $this->query($SQLString);

            $SQLString = "INSERT INTO menu (`site_id`, `name`, `module`, `pos`, `level`, `url`, `visible`, `formular`)
                    VALUES ('$site_id','Referenzen','cms','4','0','Referenzen','1','0')";
            $this->query($SQLString);

            $SQLString = "INSERT INTO menu (`site_id`, `name`, `module`, `pos`, `level`, `url`, `visible`, `formular`)
                    VALUES ('$site_id','Kontakt','cms','5','0','Kontakt','1','1')";
            $this->query($SQLString);

            if ($extra['arbeitgeber']['value'] != '')
                $pagetitle = $extra['arbeitgeber']['value'];
            else
                $pagetitle = $adress['surname'] . ', ' . $adress['forename'];

            $imprint = array('imprint:firma' => $extra['arbeitgeber']['value'],
                'imprint:name' => $adress['surname'],
                'imprint:vorname' => $adress['forename'],
                'imprint:strasse' => $extra['strasse']['value'] . ' ' . $extra['hausnr']['value'],
                'imprint:plz' => $extra['plz']['value'],
                'imprint:ort' => $extra['ort']['value'],
                'imprint:telefon' => $extra['prephone']['value'] . " / " . $extra['telefon']['value'],
                'imprint:email' => $adress['email'],
                'imprint:ustid' => $extra['ustid']['value'],
                'imprint:ihk' => $extra['ihk']['value'],
                'imprint:hrn' => $extra['hrnummer']['value'],
                'imprint:amtsgericht' => $extra['gericht']['value'],
                'pagetitle' => $pagetitle);

            foreach ($imprint as $row => $value) {
                $SQLString = "INSERT INTO settings (`site_id`, `key`, `value`) VALUES ('$site_id','$row','$value')";
                $this->query($SQLString);
            }
        } else {
            $dir = ($dir) ? "`dir`='$dir', " : "";
            $this->query("UPDATE apacheconfig SET " . $dir . "`branchen_id`='$as' WHERE `ID` = '$conf_id'");
        }
    }

    public function getSitesByBranche($branchen_id, $redirect = false) {
        $rd = (!$redirect) ? "" : " AND `redirect` <> '$redirect'";
        $SQLString = "SELECT `ID`, `servername`, `alias`, `redirect`, `dir`, `owner`, `branchen_id` FROM apacheconfig WHERE `branchen_id`='$branchen_id'" . $rd;
        $result = $this->query($SQLString);
        while ($zeile = $this->fetch_assoc($result)) {
            $return[] = $zeile;
        }
        return $return;
    }

    public function getSitesByOwner($owner) {
        $SQLString = "SELECT `ID`, `servername`, `alias`, `redirect`, `dir`, `owner`, `branchen_id` FROM apacheconfig WHERE `owner`='$owner'";
        $result = $this->query($SQLString);
        if ($this->errno() != 0) {
            var_dump('Fehler ' . $this->errno() . '  wegen ' . $this->error() . "\n SQL: " . $SQLString);
            die();
        }
        while ($zeile = $this->fetch_assoc($result)) {
            $return[] = $zeile;
        }
        return $return;
    }

    public function getSitesByRedirect($redirect) {

        $SQLString = "SELECT `ID`, `servername`, `alias`, `redirect`, `dir`, `owner`, `branchen_id` FROM apacheconfig WHERE `redirect`='$redirect'";
        $result = $this->query($SQLString);
        if ($this->errno() != 0) {
            var_dump('Fehler ' . $this->errno() . '  wegen ' . $this->error() . "\n SQL: " . $SQLString);
            die();
        }

        while ($zeile = $this->fetch_assoc($result)) {
            $return[] = $zeile;
        }
        return $return;
    }

    public function getSiteInfo() {
        if (!$this->siteinfo) {
            $servername = $_SERVER["SERVER_NAME"];
            $sql = "SELECT * FROM apacheconfig WHERE redirect = '" . $servername . "'";
            $result = $this->query($sql);
            $this->siteinfo = $this->fetch_assoc($result);
        }
        return $this->siteinfo;
    }

    public function getOwner() {
        $siteinfo = $this->getSiteInfo();
        return $siteinfo["owner"];
    }

    public function getSite() {
        $siteinfo = $this->getSiteInfo();
        return $siteinfo["ID"];
    }

    public function checkDomain($domain) {
        $domain = strtolower(\Utils::escape($domain));
        $sql = "SELECT id FROM apacheconfig WHERE redirect LIKE '" . $domain . "'";
        $result = $this->query($sql);
        return ($this->num_rows($result) > 0) ? false : true;
    }

    /**
     * Diese Funktion holt den Hash Wert	 
     *
     * @param String $hash Wert aus microtime und email Adresse
     * */
    public function getLinkByHash($hash) {
        $hash = Utils::escape($hash);
        $link = $this->query("SELECT * FROM hash WHERE hash LIKE '" . $hash . "'");
        if ($result = $this->fetch_assoc($link))
            return $result;
        return false;
    }

    /**
     * Diese Funktion speichert den Hash Wert in der DB 
     *
     * @param String $hash Wert aus microtime und email Adresse
     * @param Integer $userid ID des Users, für den der Link gilt
     * @param String $url URL, zu der verlinkt werden soll
     * @param Integer $days Tage, die der Link gültig sein soll. Standard: 2
     * */
    public function setHash($hash, $userid, $orderid, $url, $days = 2) {
        $hash = Utils::escape($hash);
        $url = Utils::escape($url);
        $userid = intval($userid);
        $days = intval($days);

        $sql = "INSERT INTO hash (hash, user_id, order_id, url, valid_until) 
					VALUES('" . $hash . "','$userid','$orderid','$url', DATE_ADD(NOW(),INTERVAL " . $days . " DAY))
					";
        $this->query($sql);
    }

    /**
     * Diese Funktion löscht den Datensatz des ermittelten Hashwertes nach erfolgreichem zurücksetzen des Passwortes 
     *
     * @param String $hash Wert aus microtime und email Adresse
     * @param Integer $userid
     * */
    public function deleteHash($hash) {
        $hash = Utils::escape($hash);
        $sql = "DELETE FROM hash 
					WHERE hash = '" . $hash . "'
					LIMIT 1
					";
        $this->query($sql);
    }

    public function saveSetting($key, $val, $site = 0) {
        $site = intval($site);
        if ($site < 1)
            $site = Application::getInstance()->getSite();
        $key = Utils::escape($key);
        $val = Utils::escape($val);
        if ($this->getSettings("", $key) !== false) {
            $query = "UPDATE settings SET `value` = '$val' WHERE site_id = $site AND `key` = '$key'";
        } else {
            $query = "INSERT INTO settings (site_id, `key`, `value`) VALUES ($site, '$key', '$val')";
        }
        $this->query($query);
    }

    public function getSettings($type = "", $key = "") {
        $site = Application::getInstance()->getSite();
        $filter = "";
        if ($type != "") {
            $type = Utils::escape($type);
            $filter .= " AND `key` LIKE '" . $type . ":%'";
        }
        if ($key != "") {
            $key = Utils::escape($key);
            $filter .= " AND `key` = '$key'";
        }

        $return = array();
        $result = $this->query("SELECT `key`, `value` FROM settings WHERE site_id = " . $site . $filter);
        while ($tmp = $this->fetch_assoc($result)) {
            $tmpkey = $tmp["key"];
            $rc = 1;
            if ($type != "")
                $tmpkey = str_replace($type . ":", "", $tmpkey, $rc);
            $return[$tmpkey] = $tmp["value"];
        }

        if ($key != "") {
            if (key_exists($key, $return))
                return $return[$key];
            return false;
        }
        return $return;
    }

    public function getCmsMenu() {
        if ($this->navi) {
            reset($this->navi);
            return $this->navi;
        }
        $site = Application::getInstance()->getSite();
        #$visible = (Application::getInstance()->adminmode()) ? "" : " AND visible > 0";
        $url = Utils::escape($url);
        $result = $this->query("SELECT id, pid, name, title, keywords, description, url, module, level, formular, visible, header_image, acl_rights FROM menu WHERE site_id = " . $site . $visible . " ORDER BY pos");
        $return = array();
        $level_gesperrt = 999;
        while ($tmp = $this->fetch_assoc($result)) {
			if ($tmp["level"] > $level_gesperrt) continue;
			$level_gesperrt = 999;
			$access = true;
            if ($tmp["acl_rights"] != "") {
                $access = false;
                $rights = explode(",", $tmp["acl_rights"]);
                foreach ($rights AS $r) {
                    if (\Acl::getInstance()->checkRight(trim($r)))
                        $access = true;
                }
            }
            if (!$tmp["visible"] && \Request::getInstance()->site != "admin" && !\SHOW_INVISIBLE_NAVI_ITEMS)
                $access = false;
            if (!$access) {
				$level_gesperrt = $tmp["level"];
                continue;
			}
            $return[$tmp["id"]] = $tmp;
        }
        $this->navi = $return;
        return $return;
    }

    public function saveNaviOrder($id, $pos) {
        $site = Application::getInstance()->getSite();
        $id = intval($id);
        $pos = intval($pos);
        $this->query("UPDATE menu SET pos = '$pos' WHERE site_id = " . $site . " AND id = '$id'");
    }

    public function getFirstSubmenuItem($id) {
        $id = intval($id);
        $sql = "SELECT url FROM menu WHERE pid = $id AND visible>0 ORDER BY pos";
        $result = $this->query($sql);
        if ($sub = $this->fetch_assoc($result))
            return $sub["url"];
        return false;
    }

    public function saveNaviItem($id, $name, $level = 0, $pid = 0, $pos = 0, $visible = 1) {
        $site = Application::getInstance()->getSite();
        $id = intval($id);
        $name = Utils::escape($name);
        $level = intval($level);
        $visible = intval($visible);
        $pid = intval($pid);
        $pos = intval($pos);
        $this->query("UPDATE menu SET name = '$name', level='$level', pid='$pid', pos='$pos', visible='$visible' WHERE site_id = " . $site . " AND id = '$id'");
    }

    public function saveSeo($id, $url, $title, $description, $keywords) {
        $site = Application::getInstance()->getSite();
        $id = intval($id);
        $name = Utils::escape($url);
        $title = Utils::escape($title);
        $description = Utils::escape($description);
        $keywords = Utils::escape($keywords);
        $this->query("UPDATE menu SET `url` = '$url', `title`='$title', `description`='$description', `keywords`='$keywords' WHERE site_id = " . $site . " AND id = '$id'");
    }

    public function getCmsContent($id) {
        $id = intval($id);
        $site = \Application::getInstance()->getSite();
        $sql = "SELECT `content`.`id` AS content_id, `content`.`site_id`, `content`.`menu_id`, `content`.`pos`, `content`.`type`, `content`.`title`, 
                            `content`.`title_en`, `content`.`title_cz`, `content`.`content`, `content`.`content_en`,  `content`.`content_cz`, 
                            `content`.`created_at`, `content`.`modified_at`, `content`.`created_by`, `content`.`modified_by`, `content`.`preview_link`, 
                            `content`.`preview_anchor`, `content`.`preview_image`, `content`.`download_type`, `content`.`download_name`, `content`.`download_link`, `content`.`download_size`, 
                            `content`.`valid_from`, `content`.`valid_until`        
                      FROM content

                WHERE content.deleted < 1 AND site_id = '$site' AND menu_id = '" . $id . "' 
                GROUP BY content_id
                ORDER BY pos";
        $result = $this->query($sql);
        $return = array();
        while ($tmp = $this->fetch_assoc($result))
            $return[] = $tmp;
        return $return;
    }

    public function getCmsItem($id) {
        $id = intval($id);
        $site = \Application::getInstance()->getSite();
        $sql = "SELECT * FROM content WHERE `deleted` < 1 AND site_id = '$site' AND id = '" . $id . "' ORDER BY pos";
        $result = $this->query($sql);
        return $this->fetch_assoc($result);
    }

    public function getCmsSite($url) {
        $navi = $this->getActiveNaviItems($url);
        return end($navi);


        $url = Utils::escape($url);
        $navi = $this->getCmsMenu();
        if ($url == "")
            return $this->navi[0];
        else {
            foreach ($this->navi AS $n)
                if ($n["url"] == $url)
                    return $n;
        }
    }

    public function getCmsIdByUrl($url) {
        $url = Utils::secure($url);
        $site = Application::getInstance()->getSite();
        $result = $this->query("SELECT id FROM menu WHERE url='$url' AND site_id = '$site'");
        if ($tmp = $this->fetch_assoc($result))
            return $tmp["id"];
        else
            return false;
    }

    public function getActiveNaviItems($url) {
        $navi = $this->getCmsMenu();
        if ($url == "") {
            $n = reset($navi);
            return array($n["id"] => $n);
        }

        $active = array();
        foreach ($navi AS $n) {
            if ($url == $n["url"]) {
                $pid = $n["pid"];
                $active[$n["id"]] = $n;
            }
        }
        while ($pid > 0) {
            $n = $navi[$pid];
            $pid = $n["pid"];
            $active[$n["id"]] = $n;
        }

        return array_reverse($active, true);
    }

    public function getActiveNaviItem() {
        if ($this->active !== false)
            return $this->active;
        $this->active = 0;
        $request = Request::getInstance();

        //Zuweisen der aktuellen Categorie-ID an active, auch wenn über Details kein Request zur Verfügung steht
        if (
                $request->site == "shop" &&
                $request->action == "details"
        ) {
            $source = array('table' => 'prod',
                'search' => 'product_id',
                'term' => array(
                    'term0' => intval($request->args[0]),
                    'term1' => $request->args[1]
                )
            );
            $result = $this->search($source);
            if (count($result) > 0)
                $this->active = $result[0]["id_product_category"];
        } else {
            $this->active = intval($request->args[0]);
        }
        return $this->active;
    }

    public function updateNaviItem($url, $post) {
        $site = Application::getInstance()->getSite();
        $menuname = Utils::escape($post["menuname"]);
        $title = Utils::escape($post["title"]);
        $keywords = Utils::escape($post["keywords"]);
        $description = Utils::escape($post["description"]);
        $formular = intval($post["show_form"]);
        $url = Utils::escape($url);

        $set = array();
        if (isset($post["menuname"]))
            $set[] = "`name`='$menuname'";
        if (isset($post["title"]))
            $set[] = "`title`='$title'";
        if (isset($post["keywords"]))
            $set[] = "`keywords`='$keywords'";
        if (isset($post["description"]))
            $set[] = "`description`='$description'";
        $set[] = "`formular`='$formular'";

        $sql = "
				UPDATE menu SET
				" . implode(", ", $set) . "
				WHERE url = '" . $url . "' AND site_id = " . $site;

        $result = $this->query($sql);
//			error_log($sql);

        return true;
    }

    public function saveNaviImage($menu_id, $image) {
        $site = Application::getInstance()->getSite();
        $menu_id = intval($menu_id);
        $image = Utils::escape($image);

        $sql = "
				UPDATE menu SET
				header_image = '$image'
				WHERE id = '" . $menu_id . "' AND site_id = " . $site;

        $result = $this->query($sql);
//        error_log($sql);

        return true;
    }

    public function deleteNaviImage($menu_id) {
        $site = Application::getInstance()->getSite();
        $menu_id = intval($menu_id);
        $image = Utils::escape($image);

        $sql = "
				UPDATE menu SET
				header_image = ''
				WHERE id = '" . $menu_id . "' AND site_id = " . $site;

        $result = $this->query($sql);

        return true;
    }

    public function createCmsMenu($name, $module = 'cms', $formular) {
        $site = Application::getInstance()->getSite();
        $name = Utils::escape($name);
        $module = Utils::escape($module);
        $formular = Utils::escape($formular);
        $search = array("ä", "ö", "ü", "Ä", "Ö", "Ü", "ß");
        $replace = array("ae", "oe", "ue", "Ae", "Oe", "Ue", "ss");
        $url = str_replace($search, $replace, $name);
        $url = preg_replace("'[\W|_]+'is", "-", $url);

        if ($this->getCmsIdByUrl($url) > 0) {
            $counter = 1;
            while ($this->getCmsIdByUrl($url . "_" . $counter) > 0)
                $counter++;
            $url .= "_" . $counter;
        }

        $tmp = $this->query("SELECT MAX(pos) AS m FROM menu WHERE site_id = " . $site);
        if ($pos = $this->fetch_assoc($tmp))
            $pos = ++$pos["m"];
        else
            $pos = 1;

        $this->query("INSERT INTO menu (name, url, pos, site_id, module, formular, visible) VALUES ('$name', '$url', '$pos', '$site', '$module', '$formular', 1)");
    }

    public function deleteNaviItem($url) {
        $url = Utils::secure($url);
        $site = Application::getInstance()->getSite();
        $tmp = $this->query("SELECT id FROM menu WHERE url = '$url' AND site_id = '$site'");
        if ($tmp = $this->fetch_assoc($tmp)) {
            $id = $tmp["id"];
            $this->query("DELETE FROM menu WHERE id = '$id'");
            $this->query("DELETE FROM content WHERE preview_link = '$id'");
        }
    }

    /**
     * Diese Funktion gibt einen Menüpunkt anhand seiner ID zurück
     *
     * */
    public function getMenuByID($id) {
        $result = $this->query("SELECT * FROM menu WHERE id=" . $id);
        $return = array();
        if ($tmp = $this->fetch_assoc($result)) {
            $return = $tmp;
        }
        return $return;
    }

    /**
     *  Diese Funktion fügt für einen Benutzer ein Recht hinzu. Es wird nicht geprüft, ob dieses Recht bereits vorhanden ist.
     *  Diese Prüfung muss vorher erfolgen.
     * @param type $user_id
     * @param type $right_id 
     */
    public function insertUserRight($user_id, $right_id) {
        $SQLString = "INSERT INTO user_right (`user_id`, `right_id`) VALUES ('$user_id', '$right_id');";
        $this->query($SQLString);
    }

    /**
     * Speichert eine ShortURL in der DB
     *
     * @param String $url Die URL, die zu speichern ist
     *
     * @return Integer Die ID, die der Eintrag hat.
     * */
    public function shorturlSaveUrl($url) {
        $user_id = \Application::getInstance()->getUser()->id;
        $url = \Utils::escape($url);

        // Prüfen, ob die URL schon bekannt ist. Dann die ShortURL zurückgeben ohne zu speichern.
        $tmp = $this->query("SELECT id FROM shorturl WHERE url LIKE '$url' AND creator = '$user_id'");
        if ($u = $this->fetch_assoc($tmp))
            return $u["id"];

        // Noch nicht bekannt, dann neu anlegen.
        $this->query("INSERT INTO shorturl (url, creator) VALUES ('$url', '$user_id')");
        return $this->getLastInsertID();
    }

    /**
     * Holt eine URL anhand der ID aus der DB
     *
     * @param Int $id Die ID des Eintrages
     * @param Boolean $count Soll der zugriff gezählt werden?
     *
     * @return String Die URL bzw. false, wenn nicht vorhanden.
     * */
    public function shorturlGetUrlByID($id, $count) {
        $id = intval($id);
        $tmp = $this->query("SELECT * FROM shorturl WHERE id = '$id'");
        if ($u = $this->fetch_assoc($tmp)) {
            if ($count)
                $this->query("UPDATE shorturl SET `count` = `count`+1 WHERE id = '$id'");
            return $u;
        }
        return false;
    }

    public function shorturlGetMyURLs() {
        $user_id = \Application::getInstance()->getUser()->id;
        return $this->query("SELECT * FROM shorturl WHERE creator = '$user_id' ORDER BY id");
    }

    /**
     * Holt alle tooltipps der aktuellen Seite.
     *
     * @return Arrray Array mit den Tooltipps. Key: ID des HTML-Elements, Val: Tooltipp als Text
     * */
    public function getTooltipps() {
        $site = \Request::getInstance()->site;
        $action = \Request::getInstance()->action;
        if (!$action)
            $action = "index";
        $result = $this->query("SELECT element_id, tooltipp FROM tooltipps WHERE site='$site' AND action='$action'");
        $return = array();
        while ($tmp = $this->fetch_assoc($result))
            $return[$tmp["element_id"]] = $tmp["tooltipp"];
        return $return;
    }

    /**
     * Holt alle tooltipps der aktuellen Seite.
     *
     * @return Arrray Array mit den Tooltipps. Key: ID des HTML-Elements, Val: Tooltipp als Text
     * */
    public function saveTooltipp($site, $action, $id, $tipp) {
        $site = \Utils::escape($site);
        $action = \Utils::escape($action);
        $id = \Utils::escape($id);
        $tipp = str_replace("\"", "&quot;", $tipp);
        $tipp = \Utils::escape($tipp);

        $sql = "
			INSERT INTO tooltipps
				(site, action, element_id, tooltipp)
			VALUES
				('$site', '$action', '$id', '$tipp')
			ON DUPLICATE KEY
				UPDATE tooltipp = '$tipp'
		";

        $this->query($sql);
    }

    public function getMailTemplate($template=0) {
        $id = intval($template);
        $filter = ($id != 0) ? "WHERE id = ".$id : "ORDER BY id";
        $sql = "SELECT * FROM mail_templates ".$filter;
        $result = $this->query($sql);
        if ($id != 0) {
            $tpl = $this->fetch_assoc($result);
            if (is_array($tpl)) $return = $tpl["template"];
            else $return = "";
        } else {
            $return = array();
            while($tmp = $this->fetch_assoc($result)) $return[] = $tmp;
        }
        return $return;
    }

    public function saveMailTemplate($id, $template) {
        $id = intval($id);
        $template = \Utils::escape($template);
        $this->query("UPDATE mail_templates SET template = '$template' WHERE id = $id");
    }

        
}
?>
