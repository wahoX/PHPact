<?php
namespace Sites;

/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
use \Controller as Controller;

class Api extends \Controller
{
	private $auth;
	private $result;
    private $user = false;
    	
    public function __construct($template = "", $sub = "") {
		parent::__construct($template, $sub);
		$headers = getallheaders();
		$tmp = str_replace("Nr1WS ", "", $headers["Authorization"]);
		$tmp = explode(":", $tmp);
		$key = $tmp[0];
		unset($tmp[0]);
		$hash = implode(":", $tmp);
		$this->auth = array(
			"key" => $key,
			"hash" => $hash,
			"secret" => "",
            "user" => 0
		);
		$this->result = new \stdClass();
		$auth = $this->authorize();
		$this->result->authorization = $auth;

        if (!$auth) {
            $this->result->Success = "0";
            $this->result->Errorcode = "99";
            $this->result->Error = "HMAC Authentifikation fehlgeschlagen.";
        } else {
            // Ausführen der Anfrage
            $function = $this->request->action;
            $function .= "_";
            $function .= strtolower($this->request->method);
            if(method_exists($this, $function)) {
                $this->$function(); 
            } else {
                $this->result->Success = "0";
                $this->result->Errorcode = "98";
                $this->result->Error = "Das aufgerufene Objekt oder die Methode existiert nicht.";
            }
        }

        // Log der Anfrage
        $request = array();
        switch ($this->request->method) {
            case "GET" :
                $request = $this->request->get;
                break;
            case "POST" :
                $request = $this->request->post;
                break;
            case "PUT" :
                $request = $this->request->put;
                break;
            case "DELETE" :
                $request = $this->request->delete;
                break;
        }
        if (isset($request["file_base64"])) {
            $len = strlen(base64_decode($request["file_base64"]));
            $request["file_base64"] = "Datei mit $len Byte";
        }
        if (isset($request["password"])) {
            $len = strlen(base64_decode($request["file_base64"]));
            $request["password"] = "******";
        }
        
        $result = clone $this->result;
        if (isset($result->File)) {
			$result->File = clone $this->result->File;
            if (isset($result->File->file_base64)) {
                $result->File->file_base64 = "Datei im Log entfernt";
            }
        }
        $success = intval($this->result->Success);
        $errorcode = (isset($this->result->Errorcode)) ? intval($this->result->Errorcode) : 0;
        $errortext = (isset($this->result->Error)) ? $this->result->Error : "";
        
        $log = array(
            "objekt" => $this->request->action,
            "methode" => $this->request->method,
            "request" => json_encode($request),
            "result" => json_encode($result),
            "success" => $success,
            "errorcode" => $errorcode,
            "errortext" => $errortext,
            "created_by" => $this->auth["user"]
        );
        $this->query("log_api")->method("insert")->setData($log)->run();

        ob_end_clean();
        echo json_encode($this->result);
        die;
    }
    
    public function indexAction() {
		$this->app->forward("");
    }
    
    public function userAction() {
    }

    public function ordersAction() {
    }
    
    ############ Hilfsfunktionen #################
    
    /**
     * Prüft, ob die Anfrage ok ist. Prüfung der Zeit und Prüfung des Hashs.
     * 
     * @return Boolean true, wenn Anfrage ok, false wenn nicht.
     */
    private function authorize() {
		require_once(__DIR__."/includes/".__FUNCTION__.".php");
        
        if ($now->format("Y-m-d H:i:s") < $min->format("Y-m-d H:i:s")) return "0";
        if ($now->format("Y-m-d H:i:s") > $max->format("Y-m-d H:i:s")) return "0";
		return ($secret == $this->auth["hash"]) ? "1" : "0";
	}
    
    private function user_get() {
		require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }
    
    private function user_post() {
		require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }

    private function user_put() {
		require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }
  
    private function login() {
        if (is_object($this->user)) return true;
        $u = $this->db->getUserById($this->auth["user"]);
        $this->request->cookie = array(
			"username" => $u["email"],
			"password" => $u["password"]
		);
        $this->app->autologin(false);
        $this->acl->init();
    }
}
