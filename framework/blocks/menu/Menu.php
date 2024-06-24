<?php
namespace Blocks;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Menu extends \Controller
{
    private $menu;
    private $active;
    private $active_id;
    private $suffix;
    
    public function __construct($file="", $sub="") {
		parent::__construct($file, $sub);
		$this->menu = $this->db->getCmsMenu();
		$this->active = $this->db->getActiveNaviItems($this->request->url);
		foreach($this->active AS $a) {
			if ($a["real_active"]) $this->active_id = $a["id"];
		}

		switch (LANG) {
			case "en" :
				$this->suffix = "_en";
				break;
			case "es" :
				$this->suffix = "_es";
				break;
			case "fr" :
				$this->suffix = "_fr";
				break;
			case "it" :
				$this->suffix = "_it";
				break;
			case "nl" :
				$this->suffix = "_nl";
				break;
			case "ca" :
				$this->suffix = "_ca";
				break;
			default :
				$this->suffix = "";
				break;
		}
    }
    
    public function indexAction()
    {
		$menu = $this->getSubNavi();
		foreach ($menu AS $m) {
			$tmp = $this->getNaviItem($m, false);
			if ($this->active[$m["id"]]) {
				print_r($m);
				$subnavi = $this->getSubNavi($m["id"]);
				if (count($subnavi)) {
					$tmpsub = "";
					foreach ($subnavi AS $sm) {
						$tmpsub .= $this->getNaviItem($sm, false)->render();
					}
					if ($tmpsub != "") $tmp->SUBNAVI = "<ul class='dropdown-menu'>".$tmpsub."</ul>";
				}
			}
			$this->NAVI .= $tmp->render();
		}

    }

    public function subnaviAction()
    {
		$first = reset($this->active);
		$menu = $this->getSubNavi($first["id"]);
		$counter = 0;
		foreach ($menu AS $m) {
			$item = $this->getNaviItem($m, false);
			if (!$counter) $item->CSSCLASS .= " first";
			$tmp .= $item->render();
			$counter++;
		}
		$this->NAVI = $tmp;
    }

    public function subnavi2Action()
    {
		$first = reset($this->active);
		$item = $this->getNaviItem($first, true, false, true);
		$tmp .= $item->render();
		//$this->NAME = $first["name"];
		$menu = $this->getSubNavi($first["id"]);

		$counter = 0;
		foreach ($menu AS $m) {
			$item = $this->getNaviItem($m, false);
			$tmp .= $item->render();
			$counter++;
		}
		if ($tmp) {
			$tpl = new \Controller();
			$this->setTemplate($this->parseTag("[SUBNAVI2]"));
			$this->NAVI = $tmp;
		}
		if ($counter == 0) $this->setTemplate("");
    }

    
    public function treeAction() {
		$tpl = new \Controller();
		$tpl->setTemplate($this->parseTag("[HOME]"));
		$tmp .= $tpl->render();

		$menu = $this->getSubNavi();
		foreach ($menu AS $m) {
			$tmp .= $this->getNaviItem($m, true, true)->render();
		}
		$this->NAVI = $tmp;
    }


    public function loginAction() {
        $user = $this->app->getUser();
		$owner = $this->app->getOwner();

		if ($user->isLoggedIn())
		{
			$this->setTemplate($this->parseTag("[LOGOUT]"));
		} else {
			$this->setTemplate($this->parseTag("[LOGIN]"));
		}
    }
    
    public function adminAction()
    {
		if ($this->app->adminmode()) {
			$this->setTemplate($this->parseTag("[EDIT_MENUE]"));
		} else {
			$this->setTemplate("");
		}
    }


    /**
     * Diese Funktion erzeugt einen Menüpunkt.
     * Wenn $tree == true, dann wird auch der gesamte darunter hängende Menübaum geholt.
     *
     * @param Array $m Daten des Menüpunktes aus DB (fetch_assoc)
     * @param Boolean $tree Soll der gesamte Menübaum geholt werden?
     *
     * @return String Der Menüpunkt als listenelement
     **/
    private function getNaviItem($m, $tree=true, $breadcrumb=false, $issubmenu=false)
    {
		$this->field_title = "title".$this->suffix;
		$this->field_content = "content".$this->suffix;

		$tpl = new \Controller();
		if ($m["visible"] < 1) return $tpl;
		$tpl->setTemplate($this->parseTag("[MENUITEM]"));

        $module = $this->app->getModule($m["module"]);
		$tpl->URL = $module->getUrl($m["url".$this->suffix]);
		if ($this->active[$m["id"]]) $tpl->CSSCLASS = "active";
		if ($m["id"] == $this->active_id) $tpl->CSSCLASS .= " realactive";
		$tpl->LEVEL = 0;
		$tpl->TITLE = $m["title".$this->suffix];
		$tpl->NAME = $m["name".$this->suffix];
		if (stristr($tpl->URL, "http://") || stristr($tpl->URL, "https://")) $tpl->TARGET='target="_blank"';
		if ($breadcrumb && $this->active[$m["id"]]) {
			$this->app->addLocation($module->getUrl($m["url".$this->suffix]), $m["name".$this->suffix]);
		}
		
		#if ($onlythis) return $tpl;

		if ($tree || $this->active[$m["id"]] || 1) {
			$subnavi = $this->getSubNavi($m["id"]);
			if (count($subnavi)) {
				#$item = $this->getNaviItem($m, true, false, true);
				#$tmp = $item->render();
#				$first = clone $tpl;
#				if ($m["id"] != $this->active_id) $first->CSSCLASS = "";
#				$first->render();
#				$first->clearAll();
#				$tmp = $first->render();
				foreach($subnavi AS $m) {
					$tmp .= $this->getNaviItem($m, $tree, $breadcrumb, $issubmenu);
				}
				if (trim($tmp) != "" && !$issubmenu) {
					$tpl->SUBNAVI = "<ul class='dropdown-menu'>".$tmp."</ul>";
					$tpl->DROPDOWN = "dropdown-toggle\" data-toggle=\"dropdown";
				}
			}
		}
		$tpl->render();
		$tpl->clearAll();
		return $tpl;

    }
    
    /**
     * Diese Funktion holt alle Untermenüpunkte eines Menüpunktes.
     *
     * @param Integer $pid die ID des Menüpunktes, dessen Untermenüpunkte geholt werden sollen
     *
     * @return Array Alle Untermenüpunkte
     **/
    private function getSubNavi($pid=0)
    {
		$tmp = array();
		reset ($this->menu);
		foreach($this->menu AS $m) {
			if ($m["pid"] == $pid) $tmp[$m["id"]] = $m;
		}
		return $tmp;
    }
}

?>
