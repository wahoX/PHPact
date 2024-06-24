<?php
$user = $this->app->getUser();
$user->update();
$forms = array("persoenlich");
$what = $form;
if (!in_array($what, $forms)) {
	return;
}
$this->WHAT = $form;

$tpl = $this->getSubtemplate(strtoupper($what));
$tplSelect = $this->getSubtemplate("SELECT");
$tplOption = $this->getSubtemplate("OPTION");


$user = $this->app->getUser();


reset($this->fields);
foreach($this->fields AS $k => $e) {
	$placeholder = strtoupper($k)."_SELECT_VISIBILITY";
	$tplSelect->NAME = $k."_visibility";
	foreach ($this->visible AS $key => $value) {
		$tplOption->NUM = $key;
		$tplOption->NAME = $value;
		$tplOption->SELECTED = "<%".strtoupper($k)."_VISIBILITY_".$key."_SELECTED%>";
		$tplSelect->OPTIONS .= $tplOption->render(true);
	}
	$tpl->$placeholder = $tplSelect->render(true);
}
$this->FORM = $tpl->render();
