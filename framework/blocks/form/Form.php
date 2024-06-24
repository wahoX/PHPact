<?php
namespace Blocks;

class Form extends \Controller
{
	public function indexAction()
    {
    }
    
    /**
     * Standard-Texteingabefeld
     * @param Object $args - beinhaltet: name, title (Bezeichnung), id, placeholder, pflicht
     */
    public function textAction($args) {
        $tpl = $this->getSubtemplate("TEXT");
        $this->fill($tpl, $args);        
        $this->OUTPUT = $tpl->render();
    }

    /**
     * Standard-Textarea
     * @param Object $args - beinhaltet: name, title (Bezeichnung), id, placeholder, pflicht
     */
    public function textareaAction($args) {
        $tpl = $this->getSubtemplate("TEXTAREA");
        $this->fill($tpl, $args);        
        $this->OUTPUT = $tpl->render();
    }

    
    private function fill(&$tpl, $args) {
        $uname = strtoupper($args->name);
        $tpl->CLASS = "<%".$uname."_CLASS%>";
        $tpl->VALUE = "<%".$uname."_VALUE%>";
        $tpl->MESSAGE = "<%".$uname."_MESSAGE%>";
        $tpl->VALIDATE = "<%".$uname."_VALIDATE%>";
        $tpl->NAME = $args->name;
        $tpl->TITLE = $args->title;
        $tpl->ID = $args->id;
        $tpl->PLACEHOLDER = $args->placeholder;
        if ($args->pflicht) $tpl->PFLICHT = " <sup><span class='glyphicon glyphicon-asterisk glyphicon-red' id='asterisk_$id'></span></sup>";
    }

}

?>
