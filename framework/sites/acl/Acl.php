<?php
namespace Sites;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Acl extends \Controller
{
	public function indexAction()
	{
		$recht = $this->acl->checkRight(S_ADMIN);
		if (!$recht){
			$this->app->addError("Sie haben kein Recht, diese Seite zu betreten!");
			$this->setTemplate("");
		}
		//$this->render();
	}
	
	public function rightsAction()
	{
		$this->app->registerCSS("res/css/acl.css");
		$recht = $this->acl->checkRight(S_ADMIN);
		if (!$recht){
			$this->app->addError("Sie haben kein Recht, diese Seite zu betreten!");
			$this->setTemplate("");
			$this->render();
			return;
		}
		
		$row = new \Controller();
		$row->setTemplate($this->parseTag("[ROW]"));
		$action = new \Controller();
		$action->setTemplate($this->parseTag("[ACTION]"));
		
		$rights = $this->db->getAllRights();
		$lastid = 0;
		$actions = array();
		$rows = array();
		
		foreach($rights AS $id => $r)
		{
			$actions = array();
			foreach ($r["actions"] AS $a)
			{
				$action->ID = $a["id"];
				$action->NAME = $a["name"];
				$action->DESCRIPTION = $a["description"];
				array_push($actions, $action->render());
				$action->resetParser();
			}
			$row->ACTIONS = implode(", ", $actions);
			$row->ID = $id;
			$row->NAME = $r["name"];
			$row->DESCRIPTION = $r["description"];
			array_push($rows, $row->render());
			$row->resetParser();
		}
		$this->ROWS = implode("\n", $rows);
	}



	public function actionsAction()
	{
		$this->app->registerCSS("res/css/acl.css");
		$recht = $this->acl->checkRight(S_ADMIN);
		if (!$recht){
			$this->app->addError("Sie haben kein Recht, diese Seite zu betreten!");
			$this->setTemplate("");
			$this->render();
			return;
		}
		
		$row = new \Controller();
		$row->setTemplate($this->parseTag("[ROW]"));
		$right = new \Controller();
		$right->setTemplate($this->parseTag("[RIGHT]"));
		
		$actions = $this->db->getAllActions();
		$rights = array();
		$rows = array();
		
		foreach($actions AS $id => $r)
		{
			$rights = array();
			foreach ($r["rights"] AS $a)
			{
				$right->ID = $a["id"];
				$right->NAME = $a["name"];
				$right->DESCRIPTION = $a["description"];
				array_push($rights, $right->render());
				$right->resetParser();
			}
			$row->RIGHTS = implode(", ", $rights);
			$row->ID = $id;
			$row->NAME = $r["name"];
			$row->DESCRIPTION = $r["description"];
			array_push($rows, $row->render());
			$row->resetParser();
		}
		$this->ROWS = implode("\n", $rows);
	}
	
	
}
