<?php
namespace Sites;
use Blocks;

/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
use \Controller as Controller;

class Admin extends \Controller
{
    public function seoAction() {
        require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }

}
