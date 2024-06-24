<?php
/**
 * \namespace \
 */

	class Request extends Singleton
	{
		private $data;
		
		protected function __construct() {
            
            $this->method = $_SERVER["REQUEST_METHOD"];
            
			// POST holen
			$tmp = array();
			reset($_POST);
			foreach($_POST AS $k => $v) {
				if (!is_array($v)) $v = \Utils::decodeAjaxString($v);
				$tmp[$k] = $v;
			}
			$this->post = $tmp;

			// GET holen
			$tmp = array();
			reset($_GET);
			foreach($_GET AS $k => $v) {
				if (!is_array($v)) $v = \Utils::decodeAjaxString($v);
				$tmp[$k] = $v;
			}

			$this->get = $tmp;
            
            $this->put = array();
            if ($this->method == "PUT") {
                $put = array();
                parse_str(file_get_contents("php://input"), $put);
                $this->put = $put;
            }

            $this->delete = array();
            if ($this->method == "DELETE") {
                $delete = array();
                parse_str(file_get_contents("php://input"), $delete);
                $this->delete = $delete;
            }

			// Cookies holen
			$tmp = array();
			reset($_COOKIE);
			foreach($_COOKIE AS $k => $v) $tmp[$k] = $v;
			$this->cookie = $tmp;

			// Die aufgerufene URL auseinanderfriemeln ...
			$this->root = str_replace("index.php", "", $_SERVER["SCRIPT_NAME"]);
			$regex = "|^".str_replace('/', '\/', $this->root)."|is";
			$dir = preg_replace($regex, "", $_SERVER["REQUEST_URI"]);

			// GET abschneiden
			$dir = explode("?", $dir);
			$this->url = $dir[0];
			// Rest zu einem Array aufteilen
			$dir[0] = Utils::str_encode($dir[0]);
			$dir = explode("/", $dir[0]);
			$dir = array_map("urldecode", $dir);
#			$dir = array_map("utf8_encode", $dir);
			// Prüfung, ob Ajax. Wenn ja, dann ajax=true und 1. Wert entfernen
			$this->ajax = false;
			if ($dir[0] == "ajax")
			{
				$this->ajax = true;
				unset($dir[0]);
				$dir = implode("/", $dir);
				$dir = explode("/", $dir);
				header('Content-type: application/xml');
			} else if ($this->isAjaxRequest()) {
				$this->ajax = true;
				header('Content-type: application/xml');
			} else {
                Application::getInstance()->setFilemanagerFolder(false);
            }

			// prüfen, ob Alias
			$dir = implode("/", $dir);
			$tmp = Application::getInstance()->getUrlByAlias($dir);
			if ($tmp) $dir = $tmp;
			$dir = explode("/", $dir);

			// Site und Action bestimmen und aus Array entfernen
			$this->site = (isset($dir[0])) ? $dir[0] : "home";
			$this->action = (isset($dir[1])) ? $dir[1] : "index";
			$this->action = str_replace(array(".xml", ".html"), array("", ""), $this->action);
			unset($dir[0]);
			unset($dir[1]);
			$dir = implode("/", $dir);
			$dir = explode("/", $dir);
			
			// Ab in $args
			$this->args = $dir;

			$this->ip = $_SERVER["REMOTE_ADDR"];
			$this->referrer = (isset($_SERVER["HTTP_REFERER"])) ? $_SERVER["HTTP_REFERER"] : "";
			if (!stristr($this->referrer, $_SERVER["SERVER_NAME"])) $this->referrer = $_SESSION["referrer"];
			$this->referer = $this->referrer;
			$_SESSION["referrer"] = $_SERVER["REQUEST_URI"];
			
			$history = $_SESSION["history"];
			if (!$history) $history = array();
			if ($history[0] != $_SERVER["REQUEST_URI"] && !$this->ajax) {
				$history = array_reverse($history);
				$history[] = $_SERVER["REQUEST_URI"];
				$history = array_reverse($history);
				$history = array_slice($history, 0, 10);
			}
			$_SESSION["history"] = $history;
			$this->history = $history;

            $history_advanced = $_SESSION["history_".$_SERVER["HTTP_HOST"]];
			if (!$history_advanced) {
                $history_advanced = array();
                $_SESSION["history_".$_SERVER["HTTP_HOST"]] = $history_advanced;
            };
            $this->history_advanced = $history_advanced;
		}

		public function __get($var) { return $this->data[$var]; }

		public function __set($var, $val) {
			$this->data[$var] = $val;
		}

		private function isAjaxRequest() {
			if( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && 
			strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == "xmlhttprequest" )
				return true;
			else
				return false;
		}		

		/**
		 * Gibt den GET-Parameter $var zurück
		 **/
		public function get($var) {
			return $this->get[$var];
		}

		/**
		 * Gibt den POST-Parameter $var zurück
		 **/
		public function post($var) {
			return $this->post[$var];
		}

		/**
		 * Gibt den PUT-Parameter $var zurück
		 **/
		public function put($var) {
			return $this->put[$var];
		}

        		/**
		 * Gibt den DELETE-Parameter $var zurück
		 **/
		public function delete($var) {
			return $this->delete[$var];
		}

		/**
		 * Gibt den COOKIE $var zurück
		 **/
		public function cookie($var) {
			return $this->cookie[$var];
		}

		/**
		 * Gibt das Argument $var (Integer) zurück
		 **/
		public function args($var) {
			return $this->args[$var];
		}

		/**
		 * Gibt die Anfrage-Methode zurück. Mögliche Werte: GET, POST, PUT, DELETE
		 **/
		public function method() {
			return $this->method;
		}

   		/**
		 * Legt die aktuelle Site fest
		 **/
		public function setSite($site) {
			$this->site = $site;
		}

		/**
		 * Legt die aktuelle Action fest
		 **/
		public function setAction($action) {
			$this->action = $action;
		}

		/**
		 * Gibt die aktuelle Site zurück
		 **/
		public function getSite() {
			return $this->site;
		}

		/**
		 * Gibt die aktuelle Action zurück
		 **/
		public function getAction() {
			return $this->action;
		}
		
		/**
		 * Handelt es sich um einen Ajax-Request?
		 **/
		public function isAjax() {
			return $this->ajax;
		}
		
		/**
		 * Gibt die aufgerufene URL zurück (ohne GET und POST)
		 **/
		public function getUrl() {
			return $this->url;
		}
		
		/**
		 * Gibt die IP des Users zurück
		 **/
		public function getIp() {
			return $this->ip;
		}
		
		/**
		 * Gibt den Referrer zurück
		 **/
		public function getReferrer() {
			return $this->referrer;
		}

        public function addHistory($url, $title) {
            $history = $this->history_advanced;
			if ($history[0]['url'] != $url) {
				$history = array_reverse($history);
				$history[] = array('url' => $url, 'title' => $title);
				$history = array_reverse($history);
				$history = array_slice($history, 0, 50);
			}
            $this->history_advanced = $history;
            $_SESSION["history_".$_SERVER["HTTP_HOST"]] = $history;
        }

	}
?>
