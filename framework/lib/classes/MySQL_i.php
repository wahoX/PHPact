<?php
/**
 * \namespace \
 */


	class MySQL_i
	{
		private $host, $user, $passwd, $dbname, $db;
		private $connected = FALSE;
		public $debug = true;
		public $tableinfo = [];


		protected function __construct($host = "localhost", $user = "", $passwd = "", $dbname = "")
		{
			if (!empty($host)) { $this->host = $host; }
			if (!empty($user)) { $this->user = $user; }
			if (!empty($passwd)) { $this->passwd = $passwd; }
			if (!empty($dbname)) { $this->dbname = $dbname; }

			$this->connect();
			$this->UTF8();
		}

        /**
         * @param boolean $debug
         */
		public function setDebug($debug) {
			$this->debug = $debug;
		}
		
		private function addDebug($string)
		{
			if (!$this->debug) return;
			if (class_exists("Application")) {
				Application::getInstance()->addDebug($string);
			}
		}

		private function connect()
		{
			$this->db = new mysqli($this->host, $this->user, $this->passwd, $this->dbname);

			if ($this->db === FALSE && $this->debug)
			{
				$error = $this->errno().": ".$this->error();
				$this->addException("Datenbankfehler: ".$error);
				error_log("Datenbankfehler");
			}
			else
			{
				$this->connected = TRUE;
				$this->query("set sql_mode = ''");
			}
		}

		/**
		 * Diese Funktion gibt den Link zur DB zurück
		 *
		 * @return Resource DB-Link
		 **/
		public function getLink()
		{
			return $this->db;
		}

		/**
		 * Diese Funktion gibt den Namen der DB zurück
		 *
		 * @return String DB-Link
		 **/
		public function getDbName() {
			return $this->dbname;
		}

		/**
		 * Diese Funktion führt eine SQL-Anweisung aus
		 *
		 * @param String $sql SQL-Anweisung
		 * 
		 * @return Resource Ergebnis der Anfrage
		 **/
		public function query($sql)
		{
		    $start = microtime(true);
                    $return = $this->db->query($sql);
		    $ende = microtime(true);

                    $this->addDebug("SQL-Query: ".htmlentities($sql)."\n<br />Zeit: ".($ende-$start)." Sek");

	        if ($this->errno() != 0) {
	            $this->addException("Fehler in DB-Anfrage: ".$this->error()." / SQL: ".$sql);
                return false;
	        }
			
			return $return;
		}

		// Kodiert alle Einträge eines Arrays nach UTF-8
		function array_utf8_encode($array) {
			if (is_array($array)) {
				reset($array);
				foreach ($array AS $key=>$val) {
					$array[$key] = \Utils::str_encode($val);
				}
				reset($array);
				return ($array);
			}
		}

		/**
		 * Diese Funktion gibt die Insert-ID der letzten Insert-Anweisung zurück
		 *
		 * @return Int ID
		 **/
		function getLastInsertID()
		{
			return $this->db->insert_id;
		}
		
		/**
		 * Diese Funktion gibt einen Fehlercode zurück
		 *
		 * @return Int ID
		 **/
		function errno() {
			return $this->db->errno;
		}

		/**
		 * Diese Funktion gibt einen Fehlertext zurück
		 *
		 * @return String Fehlertext
		 **/
		function error() {
			return $this->db->error;
		}

        
		/**
		 * Diese Funktion gibt eine SQL-Info zurück
		 *
		 * @return String Infotext
		 **/
		function info() {
			return $this->db->info;
		}
        
		/**
		 * Diese Funktion sichert einen String für SQL Querys ab.
		 *
		 * @return String abgesicherter String
		 **/
        function real_escape_string($string) {
            return $this->db->real_escape_string($string);
        }

		// Gib den nächsten Datensatz als assoziatives Array zurück
		function fetch_assoc($res)
		{
			return $this->array_utf8_encode($res->fetch_assoc());
		}

		// Gib den nächsten Datensatz als Array zurück
		function fetch_array($res)
		{
			return $this->array_utf8_encode($res->fetch_array());
		}

		// Gib den nächsten Datensatz als Objekt zurück
		function fetch_object($res)
		{
			return $res->fetch_object();
		}

		// Gib die Anzahl der gefundenen Datensätze zurück
		function num_rows($res)
		{
			return $res->num_rows;
		}

		// Gib die Anzahl der betroffenen Datensätze bei einem Update zurück
		function affected_rows()
		{
			return $this->db->affected_rows;
		}


		// mit Fehler abbrechen
		private function addException($msg_text)
		{
		    Application::getInstance()->addException(
				new Exception($msg_text)
			);
		}


		// setzt Datenbankname
		public function setDatabase($dbname) {
			$this->dbname = $dbname;
			if ($this->isConnected()) {
				try {
					return $this->db->select_db($this->dbname);
				}
				catch (Exception $e) {
					$error = "Could not connect to database $dbname. Reason: ".$e->getMessage();
					$this->addDebug("Database exception: ".$error);
					$this->addException($error);
					return false;
				}
			}

			return false;
		}


		public function isConnected() {
			return $this->connected;
		}


		public function UTF8()
		{
			$this->query("set names 'utf8mb4'");
		}


		public function ISO()
		{
			$this->query("set names 'latin1'");
		}

		// Destruktor
		public function __destruct()
		{
			if ($this->connected)
			{
				$this->db->close();
				$this->connected = FALSE;
			}
		}

        /**
         * Liest Spalten einer Tabelle aus information_schema.COLUMNS
         * @param String $table
         * @return Array Alle Spalten als assoziatives Array ("name" => "Datentyp")
         */
        public function getTableColumns($table) {
            $table = str_replace("`", "", $table);
            $table = \Utils::escape($table);
			if (isset($this->tableinfo[$table])) return $this->tableinfo[$table];
            $sql = "SELECT COLUMN_NAME AS name, DATA_TYPE AS typ, CHARACTER_MAXIMUM_LENGTH AS length, IS_NULLABLE AS `null` FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$this->dbname."' AND TABLE_NAME='".$table."'";
            $result = $this->query($sql);
            $return = array();
            while ($tmp = $this->fetch_object($result)) {
                $return[$tmp->name] = $tmp;
            }
            $this->tableinfo[$table] = $return;
            return $return;
        }

        /**
         * Fragt Werte aus der Tabelle $table ab und liefert ein Reslut-Objekt
         * 
         * @param String $table - Abzufragende Tabelle
         * @param String $what - Abzufragende Werte - Standard: Alles
         * @param String $where - Where-Klausel - Standard: "1" (alles)
         * @param String $order - Sortierung - Standard: keine Sortierung
         * @param Integer $start - Beginne bei Datensatz $start
         * @param Integer $limit - Begrenze Ergebnis auf $limit Datensätze - Standard: Wenn $start nicht angegeben ist, dann keine Begrenzung. Wenn $start angegeben ist, dann Begrenzung auf 100
         * @return Array Result-Objekt der Anfrage. Muss dann selbst mit db->fetch_* durchlaufen werden.
         */
        public function select($table, $what="*", $where="1", $order=null, $start=null, $limit=null, $group=null) {
            $what = explode(",", $what);
            $what = array_map("trim", $what);
            foreach ($what AS $k => $v) {
                if ($v == "*") {
                } else if (stristr($v, "COUNT(")) {
                } else if (stristr($v, ".")) {
                    $tmp = explode(".", $v);
                    $v = "";
                    foreach ($tmp AS $t) {
                        if ($t != "*") $t = $this->secureTablename($t);
                        if ($v != "") $v .= ".";
                        $v .= $t;
                    }
                } else {
                    $v = $this->secureTablename($v);
                }
                $what[$k] = $v;
            }
            $what = implode(", ", $what);
            $table = \Utils::escape($table);
            if ($order) $order = trim(\Utils::escape($order));
            if ($start) $start = intval($start);
            if ($limit) $limit = intval($limit);

            $sql = "SELECT ".$what." FROM ".$table." WHERE ".$where;
            if ($group) {
                $sql .= " GROUP BY ".$group;
            }
            if ($order) {
                $sql .= " ORDER BY ".$order;
            }
            if ($start || $limit) {
                if (!$limit) {
                    $sql .= " LIMIT ".$start.",100";
                } else if (!$start) {
                    $sql .= " LIMIT ".$limit;
                } else {
                    $sql .= " LIMIT ".$start.",".$limit;
                }
            }
    //        if (ENV == "SANDBOX") error_log($sql);
            return $this->query($sql);
        }

        /**
         * Fragt Werte aus der Tabelle $table ab und liefert ein Array
         * 
         * @param String $table - Abzufragende Tabelle
         * @param String $what - Abzufragende Werte - Standard: Alles
         * @param String $key - Dieses Feld ist der Schlüssel für das Ergebnis-Array
         * @param String $where - Where-Klausel - Standard: "1" (alles)
         * @param String $order - Sortierung - Standard: keine Sortierung
         * @param Integer $start - Beginne bei Datensatz $start
         * @param Integer $limit - Begrenze Ergebnis auf $limit Datensätze - Standard: Wenn $start nicht angegeben ist, dann keine Begrenzung. Wenn $start angegeben ist, dann Begrenzung auf 100
         * @return Array Das Ergebnis als Array mit allen gefundenen Datensätzen
         */
        public function selectArray($table, $what="*", $key=null, $where="1", $order=null, $start=null, $limit=null, $group=null) {
            $result = $this->select($table, $what, $where, $order, $start, $limit, $group);
            $return = array();
            while ($tmp = $this->fetch_assoc($result)) {
                if ($key) {
                    $return[$tmp[$key]] = $tmp;
                } else {
                    $return[] = $tmp;
                }
            }
            return $return;
        }

        /**
         * Fügt einen Datensatz in die Tabelle $table ein. Wenn $id angegeben ist, wird ein Update gemacht.
         * 
         * @param String $table
         * @param Array $data Einzufügende Daten als assoziatives Array ("Feld" => "Wert")
         * @param Mixed $id Wenn $id angegeben ist, wird ein Update gemacht, Ansonsten ein Insert
         * @param String $key Spaltenname des Feldes für Where-Klausel bei Update. Standard: "id"
         * @return Mixed Bei Insert: ID des neuen Datensatzes, bei Update: Anzahl der geänderten Datensätze
         */
        public function insert($table, Array $data, $id=null, $key="id") {
            $table = $this->secureTablename($table);
            $tableColumns = $this->getTableColumns($table);
            $user = \Application::getInstance()->getUser()->id;
            if (!$user) $user = 0;
            $user_name = \Application::getInstance()->getUser()->forename." ".\Application::getInstance()->getUser()->surname;
            $cols = array(); // Spaltennamen für INSERT
            $values = array(); // Werte für INSERT
            $set = array(); // Werte für UPDATE (`col`=val)
            $alter_wert = array();
            $neuer_wert = array();
            $uuid = false;
            $key = str_replace("`", "", $key);

            if ($id) {
                $rows = $this->selectArray($table, "*", null, $this->secureTablename($key)."='".\Utils::escape($id)."'");
                $r = $rows[0];
            } else {
                $r = [];
            // Wenn keine ID vorhanden, prüfen ob Primary Key keine Zahl ist. In dem Fall UUID erzeugen.
                switch ($tableColumns[$key]->typ) {
                    case "tinyint" :
                    case "smallint" :
                    case "mediumint" :
                    case "int" : 
                    case "bigint" :
                        $primary_numeric = true;
                        break;
                    default: 
                        $primary_numeric = false;
                        $data[$key] = $uuid = \Utils::getUUID();
                        break;
                }
            }
            if (!$id && !$primary_numeric) {
                $data[$key] = $uuid = \Utils::getUUID();
            }
            // Durchlaufen aller zu schreibenden Daten
            // Prüfung, ob es das Feld gibt und Absichern der Values
            foreach ($data AS $k => $v) {
                if (!isset($tableColumns[$k])) continue;
                if (isset($r[$k]) && $r[$k] == $v) continue;
                $alter_wert[$k] = (isset($r[$k])) ? $r[$k] : "";
                $neuer_wert[$k] = $v;
                if ($v === null && $tableColumns[$k]->null == "YES") {
                    $v = "null";
                    $neuer_wert[$k] = "NULL";
                } else {
                    switch (strtolower($tableColumns[$k]->typ)) {
                        case "tinyint" :
                        case "smallint" :
                        case "mediumint" :
                        case "int" : 
                        case "bigint" :
                            $v = intval($v);
                            break;
                        case "decimal" :
                            $v = floatval($v);
                            break;
                        default: 
                            $v = "'".\Utils::escape($v)."'";
                            break;
                    }
                }
                $k = $this->secureTablename($k);
                $cols[] = $k;
                $values[] = $v;
                $set[] = $k."=".$v;
            }
            // Keine Änderung? Dann ID zurückgeben.
            if (count($cols) == 0) return $id;

            $key = $this->secureTablename($key);

            // Zeitpunkt und User-ID der Bearbeitung hinzufügen, wenn es das Feld in der Tabelle gibt
            if (isset($tableColumns["modified_at"]) && !isset($data["modified_at"])) {
                $cols[] = "modified_at";
                $values[] = "NOW()";
                $set[] = "modified_at=NOW()";
            }
            if (isset($tableColumns["modified_by"]) && !isset($data["modified_by"])) {
                $cols[] = "modified_by";
                $values[] = $user;
                $set[] = "modified_by=".$user;
            }
            if (isset($tableColumns["modifier_id"]) && !isset($data["modifier_id"])) {
                $cols[] = "modifier_id";
                $values[] = $user;
                $set[] = "modifier_id=".$user;
            }
            if (isset($tableColumns["modifier_name"]) && !isset($data["modifier_name"])) {
                $cols[] = "modifier_name";
                $values[] = "'".\Utils::escape($user_name)."'";
                $set[] = "modifier_name='".\Utils::escape($user_name)."'";
            }


            // Zeitpunkt und User-ID der Erstellung hinzufügen, wenn es das Feld in der Tabelle gibt
            // Nur bei Insert ($id nicht angegeben)
            if (!$id) {
                if (isset($tableColumns["created_at"]) && !isset($data["created_at"])) {
                    $cols[] = "created_at";
                    $values[] = "NOW()";
                    $set[] = "created_at=NOW()";
                }
                if (isset($tableColumns["created_by"]) && !isset($data["created_by"])) {
                    $cols[] = "created_by";
                    $values[] = $user;
                    $set[] = "created_by=".$user;
                }
                if (isset($tableColumns["creator_id"]) && !isset($data["creator_id"])) {
                    $cols[] = "creator_id";
                    $values[] = $user;
                    $set[] = "creator_id=".$user;
                }
                if (isset($tableColumns["creator_name"]) && !isset($data["creator_name"])) {
                    $cols[] = "creator_name";
                    $values[] = "'".\Utils::escape($user_name)."'";
                    $set[] = "creator_name='".\Utils::escape($user_name)."'";
                }
            }

            // Wenn $id nicht angegeben: Insert
            if (!$id) {
                $sql = "INSERT INTO ".$table." (".implode(",", $cols).") VALUES (".implode(",", $values).")";
                $result = $this->query($sql);
    //            error_log($sql);
                if (!$result) return false;
                \Application::getInstance()->setValue("dbupdate_neuer_wert", $neuer_wert);
                return ($uuid) ? $uuid : $this->getLastInsertID();
            }
            // Ansonsten UPDATE
            else {
                $id = ($primary_numeric) ? intval($id) : "'".\Utils::escape($id)."'";
                $sql = "UPDATE ".$table." SET ".implode(",", $set)." WHERE ".$key."=".$id;
    //            error_log($sql);
                $this->query($sql);
                \Application::getInstance()->setValue("dbupdate_neuer_wert", $neuer_wert);
                \Application::getInstance()->setValue("dbupdate_alter_wert", $alter_wert);
                return $id;
            }

        }

        /**
         * Löscht einen oder mehrere Datensätze in die Tabelle $table.
         * Es MUSS entweder eine ID oder ein WHERE angegeben sein.
         * 
         * @param String $table
         * @param String $where Where-Klausel für DELETE
         * @param Mixed $id Wenn $id angegeben ist, wird nur der Datensatz mit der ID gelöscht
         * @param String $key Spaltenname des primary Keys. Standard: "id"
         *
         * @return boolean
         */
        public function delete($table, $where=null, $id=null, $key="id") {
            if (!$where && !$id) return false;
            $table = $this->secureTablename($table);
            $tableColumns = $this->getTableColumns($table);
            switch (strtolower($tableColumns[$key]->typ)) {
                case "tinyint" :
                case "smallint" :
                case "mediumint" :
                case "int" : 
                case "bigint" :
                    $id = intval($id);
                    break;
                case "decimal" :
                    $id = floatval($id);
                    break;
                default: 
                    $id = "'".\Utils::escape($id)."'";
                    break;
            }
            $finalwhere = "1";
            if ($where) $finalwhere .= " AND ".$where;
            if ($id) $finalwhere .= " AND ".$this->secureTablename($key)."=".$id;
            $this->query("DELETE FROM ".$table." WHERE ".$finalwhere);
            return true;
        }

        private function secureTablename($table) {
            $table = str_replace("`", "", $table);
            $table = \Utils::escape($table);
            $table = "`".$table."`";
            return $table;
        }
}
?>
