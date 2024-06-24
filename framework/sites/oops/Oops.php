<?php
namespace Sites;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Oops extends \Controller
{
        public function indexAction()
	{
                $this->app->forward("");
                header("HTTP/1.1 404");
                $this->app->addLocation("404", "Fehler 404 - Seite nicht gefunden");
                $this->app->addTitle("Fehler 404 - Seite nicht gefunden");

#                $this->NAVI = (new \Block("navi", "footer"))->render();
#                $block = new \Block("contact");
#                $this->CONTACT = $block->render();
	}
}
