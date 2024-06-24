<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AbstractClass
 *
 * @author stefan
 */
class Datacontainer
{
    private $__primaryKey = "id"; // Primary Key der Tabelle - Standard: id
    private $__table = ""; // Name der DB-Tabelle - Standard: Klassenname in Kleinbuchstaben
    private $__id = null; // ID des Objektes
    private $__data = array(); // Daten aus DB
    private $__classname = null; // Name der Klasse
    private $__band_id = null;

    /**
     * Constructor
     * 
     * @param type $id - ID des Objektes in der DB - Wenn neu, dann leer lassen
     * @param type $table - Tabelle - Standard: Name der Klasse (klein geschrieben)
     * @param type $primaryKey - Primärschlüssel der Tabelle - Standard: id
     */
	public function __construct($id=null, $table = null, $primaryKey=null, $data=null)
	{
        if ($id) $this->__id = $id;
        $this->__classname = str_replace("Datacontainer\\", "", get_class($this));

        if ($table) {
            $this->setTable($table);
        } else {
            $this->setTable(str_replace("\\", "", strtolower($this->__classname)));
        }
        
        if ($primaryKey) {
            $this->setPrimaryKey($primaryKey);
        }
        if (is_array($data)) {
            $this->fill($data);
        } else if ($id) {
            $this->load();
        }
    }
    
    public function __toString() {
        return print_r($this->__data, 1);
    }
    
    public function setNew() {
        $this->__id = null;
    }

	/**
	 * Magic Function zum Setzen von Werten. Die Variablen werden im Array $renderValues gespeichert.
	 * Alle verfügbaren Variablen werden in der Funktion render() in das zugewiesene Template geparst.
	 * Werte können einfach mit $this->variablennamne = "Wert" gesetzt werden.
	 *
	 * @PARAM string $var der Variablenname
	 * @PARAM mixed $val der Wert. Bei verwendung unseres Parsers muss $val ein String sein.
	 */
	public function __set($var, $val)
	{
        
        if ($var == "id") {
            $this->__app->addException(
				new Exception("Fehler in DataContainer: 'id' darf nicht gesetzt werden")
			);
            return false;
        }
		$this->__data[$var] = $val;
	}

	/**
	 * Magic Function zum Auslesen von Werten. Die Werte stammen aus dem Array $renderValues.
	 *
	 * @PARAM string $var der Variablenname
	 * 
	 * @RETURN mixed Der Wert der Variable. Bei Verwendung im Parser muss $val ein String sein.
	 */
	public function __get($var)
	{
		if (isset($this->__data[$var])) return $this->__data[$var];
	}
    
    public function getId() {
        return $this->__id;
    }
    
    public function getAllValues() {
        return $this->__data;
    }

    /**
     * Mit dieser Funktion kann die zu verwendende Tabelle definiert werden
     * @param type $table Name der zu verwendenden DB-Tabelle
     */
    public function setTable($table) {
        $this->__table = $table;
    }
    
    /**
     * Mit dieser Funktion kann der Primärschlüssel der DB-Tabelle definiert werden.
     * @param type $table Primärschlüssel
     */
    public function setPrimaryKey($key) {
        $this->__primaryKey = $key;
    }
    
    /**
     * Erzeugt ein Query-Objekt mit der definierten Tabelle
     * @return \Query Query-Objekt
     */
    public function query($table = null) {
        if ($table) return new \Query($table);
        return new \Query($this->__table);
    }
    
    /**
     * Lädt Daten anhand der definierten ID aus der DB
     * @return boolean true, wenn ein Datensatz gefunden wurde. Ansonsten false
     */
    public function load() {

        if (!$this->__id) {
            \Application::getInstance()->addException(
				new Exception("Fehler in DataContainer : id nicht gesetzt - es können keine Daten geladen werden.")
			);
            return false;
        }

        if ((string)$this->__id == (string)intval($this->__id)) { $id = $this->__id; }
        else $id = "'".\Utils::escape($this->__id)."'";
        $where = "`".\Utils::escape($this->__primaryKey)."` = ".$id;
        $result = $this->query()->where($where)->asArray()->run();

        if (!isset($result[0])) {
//            \Application::getInstance()->addException(
//                    new \Exception("Fehler in DataContainer: Tabelle ".$this->__table." enthält keinen Datensatz mit der id ".$this->__id)
//            );
            $this->__id = null;
            $this->__data = null;
        } else {
            $this->__data = $result[0];
        }
    }
    
    /**
     * Befüllt den Datacontainer mit übergebenen Daten.
     * @param Array $data Daten als assoziatives Array
     * @return boolean true, wenn ein Datensatz gefunden wurde. Ansonsten false
     */
    protected function fill($data) {

        if (!isset($data[$this->__primaryKey])) {
            \Application::getInstance()->addException(
                new Exception("Fehler in DataContainer : id nicht angegeben. Daten können nicht befüllt werden.")
            );
            return false;
        }
        $this->__id = $data[$this->__primaryKey];
        $this->__data = $data;
        return true;
    }
    
    /**
     * Speichert das Objekt in die Datenbank
     * 
     * @param Boolean $log Soll die Aktion geloggt werden? Standard: true
     * @return Mixed ID des gespeicherten Objektes in der DB
     */
    public function save($log = true) {
        if ($this->isEmpty()) return;
        $data = $this->__data;
        if (isset($data[$this->__primaryKey])) unset($data[$this->__primaryKey]);
        if (isset($data["created_at"])) unset($data["created_at"]);
        if (isset($data["created_by"])) unset($data["created_by"]);
        if (isset($data["modified_at"])) unset($data["modified_at"]);
        if (isset($data["modified_by"])) unset($data["modified_by"]);
        $success = false;
        if ($this->__id) {
            $method = "update";
            $id = $this->query()->method("update")->key($this->__primaryKey)->id($this->__id)->setData($data)->run();
            if ($id) $success = true;
        } else {
            $method = "insert";
            $this->__id = $this->query()->method("insert")->setData($data)->run();
            if ($this->__id) $success = true;
            $this->__data[$this->__primaryKey] = $this->__id;
        }

        if ($log && $this->__table != "log") {
            $neu = \Application::getInstance()->getValue("dbupdate_neuer_wert");
            $alt = \Application::getInstance()->getValue("dbupdate_alter_wert");
            if ($neu || $alt) {
                $log = new \Datacontainer\Log();
                $log->tabelle_name = $this->__table;
                $log->tabelle_id = $this->__id;
                $log->erfolg = ($success) ? 1 : 0;
                $log->aktion = $method;
                $log->werte = json_encode($neu);
                $log->alte_werte = json_encode($alt);
                $log->save();
            }
        }
        \Application::getInstance()->setValue("dbupdate_neuer_wert", "");
		\Application::getInstance()->setValue("dbupdate_alter_wert", "");
        if (!$success) return false;
        else return $this->__id;
    }
    
    /**
     * Prüft, ob das Objekt leer ist.
     * @return Boolean true wenn leer / false wenn nicht
     */
    public function isEmpty() {
        return (count($this->__data) > 0) ? false : true;
    }
    
    public function setVertragId($id) {
        $this->__vertrag_id = $id;
    }
    
    public function setBranchenId($id) {
        $this->__branchen_id = $id;
    }
    
    /**
     * Diese Funktion löscht den Datensatz.
     * Wenn Spalte "sichtbar" oder "deleted" vorhanden ist,  wird standardmäßig nur der Wert manipuliert (sichtbar=0 / deleted=1)
     * Wenn $final==true, dann wird der Datensatz endgültig gelöscht.
     * 
     * @param boolean $log Soll das Löschen geloggt werden? Standard: nein
     * @param boolean $final Soll endgültig gelöscht werden?
     * @return boolean
     */
    public function delete($log = true, $final = false) {
        $oldvalues = $this->getAllValues();
        $newvalues = new \stdClass();
        foreach($oldvalues AS $k=>$v) {
            $newvalues->$k = "";
        }
        if ($this->__id) {
            $saved = false;
            if (!$final && isset($this->__data["sichtbar"])) {
                $this->sichtbar="0";
                $this->save();
                $saved = true;
            }
            if (!$final && isset($this->__data["deleted"])) {
                $this->deleted="1";
                $this->save();
                $saved = true;
            }
            if ($saved) return;

            $success = $this->query()->method("delete")->key($this->__primaryKey)->id($this->__id)->run();
        } else {
            return false;
        }
        if ($log && $this->__table != "log") {
            
            $log = new \Datacontainer\Log();
            $log->band_id = $this->__band_id;
            $log->tabelle_name = $this->__table;
            $log->tabelle_id = $this->__id;
            $log->erfolg = ($success) ? 1 : 0;
            $log->aktion = "delete";
            $log->werte = json_encode($newvalues);
            $log->alte_werte = json_encode($oldvalues);
            $log->save();
        }

    }

}
