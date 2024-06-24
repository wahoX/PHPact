<?php
/**
 * Wrapper-Klasse für einfache DB Querries (Insert, Update, Select ohne JOIN)
 *
 * 
 * 
 **/
/**
 * \namespace \
 */

	class Query
	{
        private $db;
        private $table;
        private $what ="*";
        private $id = null;
        private $key = null;
        private $where = "1";
        private $order = null;
        private $group = null;
        private $start = null;
        private $limit = null;
        private $data = array();
        private $method = "select";
        private $as_array = false;
        private $as_dc_array = false;

        /**
         * Der Standard-Constructor
         * Wenn hier $table nicht übergeben wird, muss später zwingend die Methode table(..) aufgerufen werden.
         * Ansonsten gibt run in jedem Fall false zurück
         * 
         * @param String $table Die Tabelle, die angefragt werden soll.
         */
        public function __construct($table=null) {
            $this->db = \DB::getInstance();
            $this->table = $table;
        }
        
        /**
         * Definiert die Methode
         * Wenn nicht aufgerufen: select
         * Gültig: select, insert, update
         * update und insert werden gleich behandelt. Damit es ein Update wird, muss id(..) aufgerufen werden.
         * 
         * @param String $method
         * @return \Query
         */
        public function method($method) {
            $method = strtoupper($method);
            if ($method == "INSERT" || $method == "UPDATE") $this->method = "insert";
            if ($method == "SELECTARRAY") $this->method = "selectArray";
            if ($method == "DELETE") $this->method = "delete";
            return $this;
        }
        
        /**
         * Definiert die Tabelle für die Anfrage.
         * 
         * @param String $table
         * @return \Query
         */
        public function table($table) {
            $this->table = $table;
            return $this;
        }
        
        /**
         * Definiert, was abgefragt werden soll. Standard ist "*"
         * Nur interessant bei SELECT
         * 
         * @param String $what Abzufragende Felder mit Komme getrennt
         * @return \Query
         */
        public function what($what) {
            $this->what = $what;
            return $this;
        }
        
        /**
         * Definiert den zu bearbeitenden Datendatz
         * Nur interessant bei INSERT / UPDATE
         * Wenn diese Funktion aufgerufen wird und id!=null ist, wird ein UPDATE versucht. Ansonsten ein INSERT
         * 
         * @param String $id Die ID des zu bearbeitenden Datensatzes
         * @return \Query
         */
        public function id($id) {
            $this->id = $id;
            return $this;
        }
        
        /**
         * Definiert die Where-Klausel für SELECT-Anfragen. Standard: "1" (alle Datensätze)
         * 
         * @param String $where Where-Klausel
         * @return \Query
         */
        public function where($where) {
            $this->where = $where;
            return $this;
        }
        
        /**
         * Definiert die Sortierung für SELECT-Anfragen.
         * 
         * @param String $order Sortierung
         * @return \Query
         */
        public function order($order) {
            $this->order = $order;
            return $this;
        }
        
        /**
         * Definiert eine Gruppierung (GROUP BY ...) für SELECT-Anfragen.
         * 
         * @param String $group Sortierung
         * @return \Query
         */
        public function group($group) {
            $this->group = $group;
            return $this;
        }

        /**
         * Definiert Startwert für Limitierung bei SELECT-Anfragen (LIMIT $start, $limit).
         * 
         * @param Integer $start Startwert
         * @return \Query
         */
        public function start($start) {
            $this->start = intval($start);
            return $this;
        }
        
        /**
         * Definiert Anzahl der Datensätze bei SELECT-Anfragen (LIMIT $start, $limit).
         * 
         * @param Integer $limit Anzahl der Datensätze
         * @return \Query
         */
        public function limit($limit) {
            $this->limit = intval($limit);
            return $this;
        }
        
        /**
         * Definiert die zu schreibenden Daten bei Insert und Update
         * 
         * @param Array $data zu schreibende Daten als assoziatives Array (Spalte => Wert)
         * @return \Query
         */
        public function setData(Array $data) {
            if (is_array($data)) $this->data = $data;
            return $this;
        }
        
        /**
         * Fügt Daten zum $data-Array hinzu
         * 
         * @param String $key Spaltenname
         * @param String $value Wert
         * @return \Query
         */
        public function addData($key, $value) {
            $this->data[$key] = $value;
            return $this;
        }
        
        /**
         * Definiert den Schlüssel.
         * SELECT: nur interessant, wenn asArray() aufgerufen wird. In dem Fall wird der Wert dieser Spalte als Key für das Rückgabe-Array verwendet.
         * UPDATE: Wenn angegeben, wird diese Spalte für die Where-Klausel verwendet.
         * 
         * @param String $key der Schlüssel
         * @return \Query
         */
        public function key($key) {
            $this->key = $key;
            return $this;
        }
        
        /**
         * Nur für SELECT
         * Wenn diese Funktion aufgerufen wird, wird das Ergebnis als Array rurückgegeben.
         * Wenn nicht, dann als Result-Objekt
         * Bitte nicht verwenden, wenn große Datenmengen als Ergebnis erwartet werden.
         * 
         * @return \Query
         */
        public function asArray($key=null) {
            if ($key) $this->key($key);
            $this->as_array = true;
            $this->as_dc_array = false;
            return $this;
        }

        /**
         * Nur für SELECT
         * Wenn diese Funktion aufgerufen wird, wird das Ergebnis als Array mit Datacontainern rurückgegeben.
         * Wenn nicht, dann als Result-Objekt
         * Bitte nicht verwenden, wenn große Datenmengen als Ergebnis erwartet werden.
         * 
         * @return \Query
         */
        public function asDcArray() {
            $this->as_dc_array = true;
            $this->as_array = false;
            return $this;
        }

        /**
         * Führt die Anfrage aus.
         * Wenn tabelle nicht definiert ist, wird false zurück gegeben
         * 
         * @return mixed SELECT: Array bzw. Result-Objekt mit den Ergebnissen / INSERT: ID des neuen Datensatzes / UPDATE: Anzahl der betroffenen Datensätze
         */
        public function run() {
            if (!$this->table) return false;
            if ($this->method == "select") {
                if ($this->as_array) {
                    return $this->db->selectArray($this->table, $this->what, $this->key, $this->where, $this->order, $this->start, $this->limit, $this->group);
                } else if ($this->as_dc_array) {
                    $tmp = $this->db->selectArray($this->table, $this->what, $this->key, $this->where, $this->order, $this->start, $this->limit, $this->group);
                    $return = array();
                    $classname = "\\Datacontainer\\".ucfirst(strtolower($this->table));
                    foreach($tmp AS $a) {
                        $d = new $classname(null, null, null, $a);
                        $return[$d->getId()] = $d;
                    }
                    return $return;
                } else {
                    return $this->db->select($this->table, $this->what, $this->where, $this->order, $this->start, $this->limit, $this->group);
                }
            }
            
            if ($this->method == "insert") {
                if ($this->key == null) {
                    return $this->db->insert($this->table, $this->data, $this->id);
                } else {
                    return $this->db->insert($this->table, $this->data, $this->id, $this->key);
                }
            }
            
            if ($this->method == "delete") {
                if (!$this->where && !$this->id) return false;
                if ($this->key == null) {
                    return $this->db->delete($this->table, $this->where, $this->id);
                } else {
                    return $this->db->delete($this->table, $this->where, $this->id, $this->key);
                }
            }
        }

	}
?>
