<?php
/**
 * Die ACL ist für die Rechteverwaltung zuständig. Sie prüft, ob der User das Recht hat, eine bestimmte bestimmte Aktion auszuführen.
 *
 * Diese Prüfung geschieht durch den Aufruf von getRight($action_id).
 **/
/**
 * \namespace \
 */

class ACL extends Singleton
{
	// Rechte-Gruppen, in denen der User Mitglied ist
    public $rights = array();
		
	// Aktionen, auf die der User Zugriff hat
    public $actions = array();

	protected function __construct() {
        $this->init();
	}

    public function init() {
        $this->rights = Application::getInstance()->getUser()->getGroups();
        $this->actions = DB::getInstance()->getActions($this->rights);
    }

        /**
         * Diese Funktion prüft, ob der User für die Ausführung der Aktion das Recht besitzt.
         *
         * @param int $action_id Die ID der Aktion, die geprüft werden soll.
         *
         * @return boolean Hat der User das Recht für diese Aktion? true /false
         **/
        public function checkRight($action_id)
        {
		    $user = \Application::getInstance()->getUser();
			if (!$user->isLoggedIn()) {
				if ($action_id == 1) return true;
				return false;
			}
			if ($this->isMember(1)) return true;
			return (isset($this->actions[$action_id]) && $this->actions[$action_id] > 0) ? true : false;
        }
		
        /**
         * Diese Funktion prüft, ob der User Mitglied einer bestimmten Rechte-Gruppe ist.
         *
         * @param int $right_id Die ID der Rechte-Gruppe
         *
         * @return boolean Ist der User Mitgled ser Rechte-Gruppe? true /false
         **/
		public function isMember($right_id)
		{
			return (isset($this->rights[$right_id])) ? true : false;
		}
	}
?>
