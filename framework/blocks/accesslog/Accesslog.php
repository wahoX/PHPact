<?php
namespace Blocks;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Accesslog extends \Controller
{
		
	public function indexAction()
	{
        if ($this->acl->checkRight(S_ADMIN)) return;
        if ($this->request->action == "provenexpert") return;
        $now = new \DateTime();
        $log = new \Datacontainer\Log_access();
        $log->url = $this->request->url;
        $log->zeit = $now->format("Y-m-d H:i:s");
        $log->ip = $this->request->ip;
        $log->useragent = $_SERVER["HTTP_USER_AGENT"];
        $log->referrer = $_SERVER["HTTP_REFERER"];
        $log->save(false);
    }	
}
	
?>
