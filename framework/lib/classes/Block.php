<?php
/**
 * \namespace \
 */

class Block
{
	private $controller;

	public function __construct($name, $action="index", $args=NULL)
	{
		$dirname = strtolower($name);
		$filename = ucfirst($dirname);
		$classname = "\\Blocks\\".$filename;
		if (!$action) $action = "index";

		$dir = "blocks/".$dirname."/";
		$template = $dir.$action.".html";
		$template = Application::getInstance()->findFile($template);
		if (!$template) $template = $dir."_tpl.html";

		if (!class_exists($classname))
			include("blocks/".$dirname."/".$filename.".php");
		$this->controller = new $classname($template);

		$action .= "Action";
		$this->controller->$action($args);
		$this->render();
	}
	
	public function __call($name, $arguments)
	{
		return call_user_func_array(array($this->controller, $name), $arguments);
	}
	
	public function __toString()
	{
		return $this->controller->getTemplate();
	}
	
    public function getValue($name) {
        return "" . $this->controller->$name;
    }
}
