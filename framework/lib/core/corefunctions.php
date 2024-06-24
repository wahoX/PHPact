<?php
	// Funktion zum automatischen Laden von Klassen
	spl_autoload_register(function($class_name) {

		if (@include 'lib/classes/'.$class_name . '.php') return;

        $c = explode("\\", $class_name);
        if ($c[0] == "Sites") {
            if (@include 'sites/'. strtolower($c[0]) . '/' . $c[1] . '.php') return;
        }
        if ($c[0] == "Blocks") {
            if (@include 'blocks/'. strtolower($c[0]) . '/' . $c[1] . '.php') return;
        }
        if ($c[0] == "Datacontainer") {
            if (@include 'datacontainer/' . $c[1] . '.php') return;
            else {
				eval("
					namespace Datacontainer;
					class ".$c[1]." extends \Datacontainer {}				
				");
				return;
			}
        }
        if (@include strtolower($c[0]) . '/' . $c[1] . '.php') return;
        
		if (defined("INCLUDE_DIR")) {
			$IncludeDir = explode(":", INCLUDE_DIR);
			foreach($IncludeDir AS $dir) {
				#if ($dir == ".") continue;
				if ($dir == ".") $dir="";
				if (file_exists($dir.'lib/classes/'.$class_name . '.php')) {
					include $dir.'lib/classes/'.$class_name . '.php';
					return;
				}
			}
		}


	});

	function _t($template) {
		Translator::getInstance()->translate($template);
		return $template;
	}