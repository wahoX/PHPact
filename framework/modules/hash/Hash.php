<?php
namespace Module;
/**
 * Das Modul Email versendet E-Mails an User.
 */
class Hash extends \Module
{
	
	public function checkRight() {
		return true;
	}

	/**
	 * Diese Funktion holt die URL anhand eines Hashs
	 *
	 * @param Boolean $checkuser Soll die UserID geprüft werden? Standard: false
	 *
	 * @return URL für den Hash / false bei Fehler (falscher User oder Gültigkeit abgelaufen)
	 **/
	public function getUrl($checkuser = false) {
		$hash = $this->request->args[0];
		$tmp = $this->db->getLinkByHash($hash);
		$now = new \DateTime();
		$now = $now->format("Y-m-d H:i:s");
		if ($tmp["valid_until"] < $now) return false;
		if ($checkuser && $tmp["user_id"] != $user->id) return false;
		return $tmp["url"];
	}

	/**
	 * Diese Funktion prüft, ob der User die Funktion ausführen darf.
	 * 
	 * @param Boolean $guest Wenn true, dann wird beim User nur geprüft, ob er ein Gast ist. Wenn kein Gast, dann Fehler.
	 * 
	 * return Mixed false, wenn der Hash nicht existiert. Ansonsten ein Array:
	 *   user_id (Integer): ID des Users, dem der Link gehört
	 *   is_valid (Boolean): Ist der
	 *   error (Integer): Warum ist der Link nicht gültig?
	 * 
	 * Error-Codes: 
	 *   1: Hash wurde nicht gefunden.
	 *   2: Nicht mehr gültig (Zeit abgelaufen)
	 *   3: Falsche URL (Site/action passen nicht zum Hash) - Deutet auf einen Angriffsversuch hin !!!
	 * 
	 **/
	public function checkLink($guest=false)
	{
		$hash = $this->request->args[0];
		$return = array("user_id" => 0, "is_valid" => true, "error" => 0, "url" => "");

		$link = $this->db->getLinkByHash($hash);
		if (!$link || !$hash) {
			$return["is_valid"] = false;
			$return["error"] = 1;
			return $return;
		}
		
		$now = new \DateTime();
		$now = $now->format("Y-m-d H:i:s");
		if ($now > $link["valid_until"]) {
			$return["is_valid"] = false;
			$return["error"] = 2;
		}
		
/*
 		$site = $this->request->site;
		$action = $this->request->action;
		if ($site != $link["site"] || $action != $link["action"]) {
			$return["is_valid"] = false;
			$return["error"] = 3;
		}
*/
		$return["order_id"] = $link["order_id"];
		$return["user_id"] = $link["user_id"];
		$return["url"] = $link["url"];

		return $return;
	}

	public function deleteHash()
	{
		$hash = $this->request->args[0];
		$this->db->deleteHash($hash);
	}

}
