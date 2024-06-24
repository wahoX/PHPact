<?php
/**
 * \namespace \Sites
 */
namespace Sites;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
use \Controller as Controller;


class Blocks extends \Controller
{
    
    public function __construct($template = "", $tag="") {
        parent::__construct($template, $tag);
    }
    
    
	public function indexAction()
	{		
	}
	
	public function getAction()
	{
		$block = $this->request->args[0];
		$action = $this->request->args[1];
        if (!$action) $action=null;
        $args = $this->request->args[2];
    	$b = new \Block($block, $action, $args);
		$this->app->addAjaxContent($b->render());
	}
   
}
	
?>
