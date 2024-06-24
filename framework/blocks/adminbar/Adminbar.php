<?php
namespace Blocks;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Adminbar extends \Controller
{
    public function indexAction()
    {
		$user = $this->app->getUser();
		if ($this->app->getOwner() == $user->id || $this->acl->checkRight(S_ADMIN))
		{
			$this->app->registerCss("res/css/adminbar.css");
			$this->app->registerOnLoad("$('#toggle a').click(function () { $('#toggle a').toggle(); $('#panel').slideToggle(400); }); ");
			if ($this->app->adminmode()) {
				$this->LINK_EDIT = "admin/setmodus/viewer";
				$this->CLASS_EDIT = "btn btn-warning";
				$this->TEXT_EDIT = "<span class='glyphicon glyphicon-check'></span> Bearbeiten beenden";
			} else {
				$this->CLASS_EDIT = "btn btn-success";
				$this->LINK_EDIT = "admin/setmodus/admin";
				$this->TEXT_EDIT = "<span class='glyphicon glyphicon-edit'></span> Inhalte bearbeiten";
			}
		} else {
			$this->setTemplate("");
		}
    }
}

?>
