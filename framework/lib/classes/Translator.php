<?php

	/**
	* Allgemeine Übersetzungsklasse, die Textschnipsel in die gewählte Sprache übersetzt
	* Singleton-Klasse
	* 
	* \namespace \
	*/
	class Translator extends Singleton
	{

		private $suffix;

		//-----------------------------------------------------------------------------

		protected function __construct()
		{
            switch (LANG) {
                case "en" :
                case "es" :
                case "fr" :
                case "it" :
                case "nl" :
                case "ca" :
                case "de" :
                    $this->suffix = "_".LANG;
                    break;
                default :
                    $this->suffix = "_de";
                    break;
            }
		}

        public function translate(&$template)
        {
            preg_match_all("/\[\[:(.*?)\]\]/is", $template, $translate);
            $to_translate = $translate[1];
            $translations = [];
            
            foreach($translate[1] AS $t) {
                if (isset($translations[$t])) {
                    $translation = ($translations[$t]["translation".$this->suffix] != "") ? $translations[$t]["translation".$this->suffix] : $t;
                } else {
                    $translation = $t;
                }
                $template = str_replace("[[:".$t."]]", $translation, $template);
            }
        }


	}
