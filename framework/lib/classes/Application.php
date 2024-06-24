<?php
/**
 * \namespace \
 */
class Application extends Singleton
{

    // Fehlermeldungen, die erzeugt wurden
    private $errorMessages = array();

    // Erfolgsmeldungen
    private $successMessages = array();

    // Hinweise an den User
    private $hintMessages = array();

    // Debugausgaben
    private $debugMessages = array();

    // Umgebung - bei SANDBOX werden Debug-Messages ausgegeben.
    private $env;

    // Soll der Inhalt gerendert werden, oder der AjaxContent ausgegeben werden? true=render, false = ajax.
    private $renderEnabled = true;

    // Inhalt, der bei render=false ausgegeben werden soll.
    private $ajaxContent = array();

    // Arrays mit Aliases und URLs
    private $aliases = array(); // Key: Alias / Value: URL
    private $urls = array(); // Key: URL / Value: Alias
    private $includedirs = array(); // Key: URL / Value: Alias
    private $genres = array();

    private $location = array("" => "Startseite"); // Breadcrumbs

    private $modules = array();

    // verwendete Java-Scripte
    private $js = array();
    private $js_inline = "";

    // verwendete CSS-Dateien
    private $css = array();
    private $css_inline = "";

    private $root;

    // Funktionen, die onLoad ausgeführt werden sollen
    private $onload = array();

    // Weitere Angaben im Header
    private $header = array();

    // Der User
    private $User;

    /// Grund-Daten der Seite
    private $owner;
    private $site;
    private $apache;

    // Header-Daten
    public $title = "";
    private $description = "";
    private $keywords = array();
    private $fbimage;

    private $cannonical;

    private $startTime;

    private $cache;

    private $settings;

    private $values = array();

    protected function __construct()
    {
        $this->env = ENV;
        $this->cache = (defined("CACHE_ENABLED") && CACHE_ENABLED) ? true : false;
    }

    public function init() {
        if (isset($_SESSION["FORWARD"]) && $_SESSION["FORWARD"] == true) {
            $this->successMessages = $_SESSION["SUCCESS"];
            unset($_SESSION["SUCCESS"]);
            $this->hintMessages = $_SESSION["HINT"];
            unset($_SESSION["HINT"]);
            $this->errorMessages = $_SESSION["ERROR"];
            unset($_SESSION["ERROR"]);
            $this->debugMessages = $_SESSION["DEBUG"];
            if (count($this->debugMessages) > 0) {
                $this->debugMessages[] = "<span style='color:#f44; font-size:1.3em;'><br />Vorausgehende Debug-Messages stammen von der Ausführung vor einem Redirect.</span>";
            }

            unset($_SESSION["DEBUG"]);
            unset($_SESSION["FORWARD"]);
        }

        $this->User = $_SESSION["User"] ?? new \User();
        if (!is_object($this->User) || get_class($this->User) != "User") {
            $this->User = new User();
        }
    }

    /**
     * Leitet auf eine andere URL um. Erzeugte Meldungen (Erfolg, Hinweis, Fehler, Debug) werden an die neue URL übergeben.
     *
     * @param String $url Die URL, wohin weitergeleitet werden soll.
     **/
    public function forward($url, $code = "301")
    {
        $_SESSION["SUCCESS"] = $this->successMessages;
        $_SESSION["HINT"] = $this->hintMessages;
        $_SESSION["ERROR"] = $this->errorMessages;
        $_SESSION["DEBUG"] = $this->debugMessages;
        $_SESSION["FORWARD"] = true;
        $this->renderModules();
        session_write_close();
        if (!stristr($url, "http")) {
            $url = $this->getAliasByUrl($url);
            $url = Request::getInstance()->root . $url;
            $url = str_replace("//", "/", $url);
        }

        header("Location: $url", true, $code);
        die();
    }

    public function adminmode($mode = "")
    {
        $site_id = $this->getSite();
        if ($mode === "") {
            return (isset($_SESSION["adminmode_" . $site_id]) && $_SESSION["adminmode_" . $site_id] == true) ? true : false;
        }
        if ($mode == "admin") {
            $_SESSION["adminmode_" . $site_id] = true;
        } else {
            $_SESSION["adminmode_" . $site_id] = false;
        }

    }

    /**
     * Diese Funktion versucht, den User automatisch einzuloggen, wenn Userdaten im Cookie sind.
     **/
    public function autologin($forward = true)
    {
        $request = Request::getInstance();
        if (isset($request->cookie["username"])) {
            $username = $request->cookie["username"];
            $password = $request->cookie["password"];
            $this->User = new User();
            if ($username && $password) {
                if ($this->User->login($username, $password, true)) {
                    $_SESSION["User"] = $this->User;
                    $_SESSION["User_id"] = $this->User->id;
                    if ($forward) {
                        $this->forward($request->history[0]);
                    }

                } else {
                    \Utils::deleteCookie("username");
                    \Utils::deleteCookie("password");
                }
            }
        }
#            $fb = \FB::getInstance();
        //            if ($fb->isLoggedIn() && \Request::getInstance()->get["code"] != "") {
        #            if (\Request::getInstance()->get["code"] != "") {
        #                $this->User->facebook_connect($fb->getUserData());
        #            }
    }

    /**
     * Diese Funktion loggt den User ein.
     *
     * @param String $username Der Benutzername
     * @param String $password Das Passwort
     * @param Boolean $cookie true, wenn Daten als Cookie gespeichert werden sollen
     **/
    public function login($username, $password, $cookie = false, $checkvalid = true)
    {
        $User = new User();
        if ($User->login($username, $password)) {
            if ($checkvalid && $User->valid != "") {
                $_SESSION["revalidate_user"] = $User->id;
                $User = new User();
                $_SESSION["User"] = $User;
                $this->forward("konto/revalidate");
            }

            $this->renderEnabled = true;
            $_SESSION["User"] = $User;
            $_SESSION["User_id"] = $User->id;
            //if ($User->username == "") $this->forward("user/setname");
            if ($cookie) {
                \Utils::setCookie("username", $username);
                \Utils::setCookie("password", $User->password);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Diese Funktion aktualisiert die Userdaten aus der Datenbank
     * Sinnvoll, wenn der User bearbeitet wurde (Punkte bekommen, Profildaten geändert, ...)
     *
     * @param Boolean $update_password Wurde das PW geändert? Wenn ja, muss es hier ggf. im Cookie geändert werden.
     **/
    public function updateUser($update_password = false)
    {
        $this->User->update();
        $_SESSION["User"] = $this->User;
        $_SESSION["User_id"] = $this->User->id;

        if (
            isset(Request::getInstance()->cookie["username"])
            && isset(Request::getInstance()->cookie["password"])
            && $update_password
        ) {
            \Utils::setCookie("username", $this->User->username);
            \Utils::setCookie("password", $this->User->password);
        }
    }

    /**
     * Diese Funktion loggt den User aus.
     **/
    public function logout()
    {
        unset($_SESSION["client"]);
        $_SESSION = array();
        $_COOKIE["username"] = "";
        $_COOKIE["password"] = "";
        unset($_SESSION["User"]);
        unset($_SESSION["User_id"]);
        \Utils::deleteCookie("username");
        \Utils::deleteCookie("password");
        session_write_close();
        $this->User = new User();
    }

    /**
     * Prüft, ob ein User eingeloggt ist.
     **/
    public function getUser()
    {
        return $this->User;
    }

    /**
     * Setzt die Umgebung. Wenn $env = SANDBOX, dann werden Debug-Messages ausgegeben.
     *
     * @param String $env Der Modus, dem gearbeitet wird. SANDBOX oder PRODUCTIVE (standard)
     **/
    public function setEnv($env)
    {
        $this->env = $env;
        if (\ACL::getInstance()->checkRight(4)) {
            $this->env = "SANDBOX";
        }

    }

    /**
     * Diese Funktion holt ein Modul und gibt es zurück.
     *
     * @param String $name Der Name des Moduls
     *
     * @return Module Das Modul ( das Modul liegt in modules/modulname/Modulname.php )
     **/
    public function getModule($name)
    {
        $dirname = strtolower($name);
        $filename = ucfirst($name);
        if (!isset($this->modules[$name]) || !is_object($this->modules[$name])) {
            if (!file_exists("modules/" . $dirname . "/" . $filename . ".php") && !file_exists(FRAMEWORK_DIR . "modules/" . $dirname . "/" . $filename . ".php")) {
                echo "Modul " . $name . " nicht gefunden";
                #die;
            }
            require_once "modules/" . $dirname . "/" . $filename . ".php";
            $classname = "\\Module\\" . $name;
            $this->modules[$name] = new $classname();
        }
        return $this->modules[$name];
    }

    private function renderModules()
    {
        reset($this->modules);
        foreach ($this->modules as $m) {
            $m->onRender();
        }
    }

    /**
     * Diese Funktion behandelt Fehler in der Applikation.
     * Wenn SANDBOX, dann wird ein JS-Alert ausgelöst, der darauf hinweist und eine genaue Info ins Debug-Fenster geschrieben.
     *
     * @param Exception $e Das Exception-Objekt
     */
    public function addException($e)
    {
        $log = new \Datacontainer\Log_exception();
//            $log->created_by = $this->getUser()->id;
        $log->meldung = $e->getMessage();
        $log->trace = $e->getTraceAsString();
        $log->save();

        if (ENV == "SANDBOX") {
            $alert = "Es ist ein Fehler aufgetreten:\\n\\n";
            $alert .= str_replace("'", "\'", strip_tags($e->getMessage()));
            $alert .= "\\n\\nWeitere Infos im Debug-Fenster...";
            $this->registerJS("alert('" . $alert . "');", true);
            echo $e->getMessage();
            echo $e->getTraceAsString() . "\n\n";
        }
    }

    public function getSettings($key = "")
    {
        if (!$this->settings) {
            $this->settings = array();
            $tmp = DB::getInstance()->getSettings();
            foreach ($tmp as $k => $v) {
                $this->settings[$k] = $v;
            }

        }
        reset($this->settings);
        if ($key == "") {
            return ($this->settings);
        }

        return (isset($this->settings[$key])) ? $this->settings[$key] : null;
    }

    /**
     * Fügt einen Ort zum Pfad hinzu (Breadcrumbs)
     **/
    public function addLocation($url, $title)
    {
        $this->location[$url] = $title;
    }

    /**
     * Gibt das Array $location (Breadcrumbs) zurück
     **/
    public function getLocation()
    {
        reset($this->location);
        return $this->location;
    }

    /**
     * Entfernt einen breadcrumb
     **/
    public function delLocation($url)
    {
        if (isset($this->location[$url])) {
            unset($this->location[$url]);
        }

    }

    /**
     * Erzeugt eine Erfolgsmeldung
     *
     * @param String $text Erfolgsmeldung
     **/
    public function addSuccess($text)
    {
        $this->successMessages[] = $text;
    }

    /**
     * Erzeugt eine Fehlermeldung
     *
     * @param String $text Fehlermeldung
     **/
    public function addError($text)
    {
        $this->errorMessages[] = $text;
    }

    /**
     * Erzeugt einen Hinweis
     *
     * @param String $text Hinweistext
     **/
    public function addHint($text)
    {
        $this->hintMessages[] = $text;
    }

    /**
     * Erzeugt eine Debug-Ausgabe
     *
     * @param String $text Debug-Ausgabe
     **/
    public function addDebug($text)
    {
        if ($this->env == "SANDBOX") {
            $this->debugMessages[] = $text;
        }

    }

    /**
     * Registriert einen Alias zu einer URL
     *
     * @param String $alias: Kurzform der URL
     * @param String $url Die tatsächliche URL
     **/
    public function addAlias($alias, $url)
    {
        // Doppelt speichern, damit die Suche schneller läuft.
        $this->aliases[$alias] = $url;
        $this->urls[$url] = $alias;
    }

    public function setCannonical($url) {
        $this->cannonical = $url;
    }
    /**
     * Registriert eine Include-verzeichnis
     *
     * @param String $dir Das Verzeichnis, das hinzugefügt werden soll.
     **/
    public function addIncludeDir($dir)
    {
        // Doppelt speichern, damit die Suche schneller läuft.
        $this->includedirs[] = $dir;
    }

    /**
     * Diese Funktion sucht eine Datei in den definierten Include-Verzeichnissen.
     * Wird momentan nicht verwendet !!!
     *
     * @param String $file Der Dateiname
     *
     * @return Wenn gefunden, der Pfad zur Datein, ansonsten false
     **/
    public function findFile($file)
    {
        if (file_exists($file)) {
            return $file;
        }

        $includedirs = array($_SERVER["DOCUMENT_ROOT"], \FRAMEWORK_DIR);
        foreach ($includedirs as $dir) {
            $dir .= "/";
            if (file_exists($dir . $file)) {
                return $dir . $file;
            }

        }
        return false;
    }

    /**
     * Liefert die URL zu einem Alias
     *
     * @param String $alias: Kurzform der URL
     *
     * @return String Die URL. Wenn nicht bekannt, dann false.
     **/
    public function getUrlByAlias($alias)
    {
        $alias = explode("/", $alias);
        if (isset($this->aliases[$alias[0]])) {
            $alias[0] = $this->aliases[$alias[0]];
        }

        return implode("/", $alias);
    }

    /**
     * Liefert den Alias zu einer URL
     *
     * @param String $url: Die URL
     *
     * @return String Der Alias. Wenn nicht bekannt, dann gibt es die übergebene URL zurück.
     **/
    public function getAliasByUrl($url)
    {
        if (isset($this->urls[$url])) {
            return $this->urls[$url];
        }

        reset($this->urls);
        foreach ($this->urls as $u => $alias) {
            if ($alias == "") {
                continue;
            }

            // if ($url=="branchenbuch/searchbranchen") echo $u.":".$alias."<br />";
            $u .= "/";
            if (substr($url, 0, strlen($u)) == $u) {
                return $alias . "/" . substr($url, strlen($u));
            }
        }

        return $url;

    }

    /**
     * Definiert Infos für den Seiten-Header.
     * Die Daten stammen z.B. aus dem Template der Seite aus dem Tag [YAML]
     *
     * @param Array $yaml Daten für den Header (Title, Description, Keywords)
     **/
    public function setHeaderInfo($yaml)
    {
        foreach ($yaml as $key => $val) {
            switch ($key) {
                case "Title":$this->addTitle($val);
                    break;
                case "Description":$this->addDescription($val);
                    break;
                case "Keywords":$this->addKeywords($val);
                    break;
                case "Master":$this->setMasterTemplate($val);
                    break;
            }
        }
    }

    public function addTitle($text)
    {
        $text = trim($text);
        if ($text != "") {
            if ($this->title != "") {
                $this->title .= " | ";
            }

            $this->title .= trim($text);
        }
    }

    public function addDescription($text)
    {
        $text = trim($text);
        if ($text != "") {
            if ($this->description != "") {
                $this->description .= " ";
            }

            $this->description .= $text;
        }
    }

    public function setFbImage($url, $overwrite = false)
    {
        if (!$overwrite && $this->fbimage != false) return false;
        $this->fbimage = $url;
    }

    public function addKeywords($array)
    {
        if (!is_array($array)) {
            $array = explode(",", $array);
            $array = array_map("trim", $array);
        }
        $this->keywords = array_merge($this->keywords, $array);
    }

    private function getRootDir()
    {
        return Request::getInstance()->root;
    }

    public function getResDir($file)
    {
        if (substr($file, 0, 3) == "res") {
            if (file_exists($_SERVER["DOCUMENT_ROOT"] . $this->getRootDir() . $file)) {} else {
                $file = preg_replace("|res/|is", "/res2/", $file, 1);
            }

        }
        if (substr($file, 0, 4) != "http") {
            $file = $this->getRootDir() . $file;
            $file = str_replace("//", "/", $file);
        }
        return $file;
    }

    /**
     * registriert eine CSS-Datei. Alle CSS Dateien werden vor der Ausgabe zusammengefasst.
     *
     * @param String $text Name der CSS-Datei in res/css/
     * @param boolean $inline Wenn true, dann wird der übergebene String als Inline-CSS in den Header geschrieben.
     **/
    public function registerCSS($css, $inline = false)
    {
        if ($inline) {
            $this->css_inline .= $css . "\n";
        } else {
            $this->css[] = $this->getResDir($css);
        }
    }

    /**
     * registriert eine JS-Datei. Alle JS Dateien werden vor der Ausgabe zusammengefasst.
     *
     * @param String $text Name der JS-Datei in res/js/
     * @param boolean $inline Wenn true, dann wird der übergebene String als Inline-JS in den Header geschrieben.
     **/
    public function registerJS($js, $inline = false)
    {
        if ($inline) {
            $this->js_inline .= $js . "\n";
        } else {
            $this->js[] = $this->getResDir($js);
        }
    }

    /**
     * registriert eine JS-Aktion, die beim Laden der Seite ausgeführt werden soll.
     *
     * @param String JS, das onload ausgeführt werden soll.
     **/
    public function registerOnLoad($onload)
    {
        $this->onload[] = $onload;
    }

    /**
     * registriert Werte für den HTML-Head-Bereich.
     *
     * @param String JS, das onload ausgeführt werden soll.
     **/
    public function registerHeader($header)
    {
        $this->header[] = $header;
    }

    /**
     * Deaktiviert die Ausgabe des Templates und aktiviert die Ausgabe von $ajaxContent
     **/
    public function disableRender()
    {
        $this->renderEnabled = false;
    }

    /**
     * Setzt ein alternatives Master-Template
     * Die Datei muss mit der Endung .html im verzeichnis master liegen.
     *
     * @param $filename Dateiname im master-Verzeichnis
     */
    public function setMasterTemplate($filename)
    {
        $filename = str_replace(".html", "", $filename);
        $this->setValue("mastertemplate", $filename);
    }

    public function setValue($var, $value)
    {
        $this->values[$var] = $value;
    }

    public function getValue($var)
    {
        return (isset($this->values[$var])) ? $this->values[$var] : null;
    }

    /**
     * Prüft, ob das Rendern aktiviert ist
     *
     * @return Boolean true, wenn aktiv / false, wenn nicht
     **/
    public function isRenderEnabled()
    {
        return $this->renderEnabled;
    }

    /**
     * Prüft, ob der Cache aktiviert ist
     *
     * @return Boolean true, wenn aktiv / false, wenn nicht
     **/
    public function isCacheEnabled()
    {
        return $this->cache;
    }

    /**
     * deaktiviert den Cache, falls er aktiv ist
     **/
    public function disableCache()
    {
        $this->cache = false;
    }

    /**
     * Erzeugt eine Ajax-Rückgabe
     *
     * @param String $text Text für Ajax-Rückgabe
     **/
    public function addAjaxContent($text)
    {
        $this->ajaxContent[] = $text;
    }

    /*
     * Definiert das Verzeichnis für den Filemanager.
     * Wird bei jedem Seitenaufruf wieder zurück gesetzt in Request-Klasse, wenn es kein Ajax-Aufruf ist.
     *
     * @param String $folder das Verzeichnis, auf das zugegriffen werden soll.
     */
    public function setFilemanagerFolder($folder)
    {
        $site = $_SERVER["HTTP_HOST"];
        $_SESSION["folder_" . $site] = $folder;
        if (!$folder) {
            unset($_SESSION["folder_" . $site]);
        }

    }

    /**
     * Setzt die Startzeit für die Zeitmessung imSandbox-Modus
     *
     * @param Micritime $time Die Zeit (microtime())
     **/
    public function setStartTime($time)
    {
        $this->startTime = $time;
    }
    /**
     * Erzeugt die Ausgabe der Anwendung.
     * Wenn render nicht deaktiviert ist, wird folgendes gemacht:
     *   - Fehler / Hinweise / Erfolgsmeldungen geparst
     *   - Im SANDBOX-Modus werden Debug-Messages geparst
     *   - registrierte JS / CSS werden zusammengefasst und gespeichert
     *   - das Template des Index-Controllers wird ausgegeben
     *
     *   ToDo: - registrierte JS / CSS werden zusammengefasst und gespeichert
     *
     * @param Controller $c Der Controller, der das Index-Template enthält.
     **/
    public function render(Controller &$c)
    {
        $this->renderModules();
        $this->registerCSS("res/css/framework.css");

        if (!$this->cannonical) $this->cannonical = "https://".$_SERVER["HTTP_HOST"]."/".Request::getInstance()->url;

        $this->registerHeader("
    <link rel='canonical' href='".$this->cannonical."' />");

        $settings = $this->getSettings();

        $debug = ob_get_clean();
        if ($this->renderEnabled) {
            if (trim($this->title) == "") {
                $title = $this->getSettings("seo_title") ?? "";
                $this->addTitle(trim($title));
            }

            if (trim($this->description) == "") {
                $description = $this->getSettings("seo_description") ?? "";
                $this->addDescription(trim($description));
            }

            if (count($this->keywords) == 0) {
                $keywords = $this->getSettings("seo_keywords") ?? [];
                $this->addKeywords($keywords);
            }

            $c->TITLE = str_replace(array('"', "'"), array("&quot;", "&apos;"), strip_tags($this->title));
            $c->DESCRIPTION = str_replace(array('"', "'"), array("&quot;", "&apos;"), strip_tags($this->description));
            $c->FBIMAGE = $this->fbimage;
            $c->SELFURL = "https://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
            $c->OGTYPE = $this->getValue("ogtype");
            $keywords = array_unique($this->keywords);
            $keywords = implode(", ", $keywords);
            $c->KEYWORDS = str_replace(array('"', "'"), array("&quot;", "&apos;"), strip_tags($keywords));
            if ($this->env == "SANDBOX") {
                $this->addDebug("<pre>" . print_r($c->request, 1) . "</pre>");
                if (trim($debug) != "") {
                    $this->addDebug("Verwendete Controller:<br /><pre>" . $debug . "</pre>");
                }

            }

            Request::getInstance()->addHistory($_SERVER["REQUEST_URI"], strip_tags($this->title));

            $tmp = new Controller();

            $end = microtime(true);
            $zeit = $end - $this->startTime;
            $this->addDebug("Ausführungszeit: $zeit Sekunden");
            $mem = memory_get_usage() / (1024 * 1024);
            $this->addDebug("Arbeitsspeicher: " . number_format($mem, 2, ",", ".") . " MB");

            // Messages parsen
            foreach (array("success", "hint", "error") as $o) {
                $messages = $o . "Messages";
                $placeholder = strtoupper($o);
                if (count($this->$messages) > 0) {
                    $text = implode("<br>", $this->$messages);
                    switch ($o) {
                        case "success" : $cssclass = "success"; break;
                        case "hint" : $cssclass = "info"; break;
                        case "error" : $cssclass = "danger"; break;
                    }
                    $this->registerOnload("flashAlert('".str_replace("'", "\\'", $text)."', '".$cssclass."', 5)");
                }
            }

            if ($this->env == "SANDBOX") {
                if (count($this->debugMessages) > 0) {
                    $tmp->setTemplate($c->parseTag("[DEBUG]"));
                    $tmp->MESSAGES = "<li>" . implode("</li><li>", $this->debugMessages) . "</li>";
                    $c->DEBUG = $tmp->render();
                }
            }

            $this->renderJS($c);
            $this->renderCSS($c);
            $this->renderOnLoad($c);
            $this->renderHeader($c);

            #print_r($c->parserValues);
        } else {
            $c = new Controller();
            $c->setTemplate(implode("\n", $this->ajaxContent));
        }
        if ($this->getValue('is_download')) {
            $template = $c->getTemplate();
        } else {
            $c->ROOT = $this->getRootDir();
            $c->SITE_ID = $this->getSite();
            $c->render();
            $c->clearAll();
            $template = $c->getTemplate();

            $template = preg_replace_callback("/\[\[LinkTo*:(.*?)\]\]/is", array($this, "replaceAliasHelper"), $template);

            Translator::getInstance()->translate($template);
            Materialicons::getInstance()->replace($template);
        }

        echo $template;
    }

    // ###################### Hilfsfunktionen

    private function replaceAliasHelper($match)
    {
        $url = $match[1];
        if (stristr($url, "http://") || stristr($url, "https://")) {
            return $url;
        }

        $url = $this->getAliasByUrl($url);
        $url = $this->getResDir($url);

        return Utils::str_encode($url);
    }

    private function renderCSS(Controller &$c)
    {
        $now = new \DateTime();
        if (count($this->css)) {
            $cssincludes = "";
            $this->css = array_unique($this->css);
            if ($this->cache) {
                $filename = $this->buildCache("css");
                $cssincludes = "<link href='" . $filename . "' rel='stylesheet' type='text/css'>\n";
            } else {
                foreach ($this->css as $css) {
                    $filter = (stristr($css, "?")) ? "" : "?" . $now->format("Ymd");
                    $cssincludes .= "<link href='" . $css . $filter . "' rel='stylesheet' type='text/css'>\n";
                }
            }

            $c->CSS_INCLUDES .= $cssincludes;
        }
        $this->css_inline .= $this->getSettings("css");
        $c->CSS_INLINE = $this->css_inline;
    }

    private function renderJS(Controller &$c)
    {
        $now = new \DateTime();
        if (count($this->js)) {
            $jsincludes = "";
            $this->js = array_unique($this->js);
            if ($this->cache) {
                $filename = $this->buildCache("js");
                $jsincludes = "<script src='" . $filename . "'></script>\n";
            } else {
                foreach ($this->js as $js) {
                    $filter = (stristr($js, "?")) ? "" : "?" . $now->format("Ymd");
                    $jsincludes .= "<script src='" . $js . $filter . "'></script>\n";
                }
            }

            $c->JS_INCLUDES .= $jsincludes;
        }
        $c->JS_INLINE = $this->js_inline;
    }

    private function renderOnLoad(Controller &$c)
    {
        if (count($this->onload)) {
            $tmp = "";
            $this->onload = array_unique($this->onload);
            foreach ($this->onload as $onload) {
                $tmp .= $onload . "\n";
            }
            $c->ONLOAD = $tmp;
        }
    }

    private function renderHeader(Controller &$c)
    {
        if (count($this->header)) {
            $tmp = "";
            $this->header = array_unique($this->header);
            foreach ($this->header as $header) {
                $tmp .= $header . "\n";
            }
            $c->HEADER = $tmp;
        }
    }

    private function buildCache($type)
    {
        $tmp = "";
        $filename = $this->getRootDir() . "res/cache/" . md5(implode(":", $this->$type)) . "." . $type;
        if (!file_exists($_SERVER["DOCUMENT_ROOT"] . $filename)) {
            $file = fopen($_SERVER["DOCUMENT_ROOT"] . $filename, "w");
            reset($this->$type);
            foreach ($this->$type as $f) {
                $tmpFilename = str_replace("res2/", "../res/", $f);

                $tmpCache = implode("", file($_SERVER["DOCUMENT_ROOT"] . $tmpFilename)) . "\n\n";

                if ($type == "css" && stristr($f, "res2/")) {
                    $tmpCache = str_replace('../images', '../../res2/images', $tmpCache);
                }
                $tmp .= $tmpCache;

            }
            fwrite($file, $tmp);
            fclose($file);
        }
        return $filename;

    }

    public function getOwner()
    {
        if ($this->owner > 0) {
            return $this->owner;
        }

        $this->owner = DB::getInstance()->getOwner();
        return $this->owner;
    }

    public function getSite()
    {
		return 1;
        if ($this->site > 0) {
            return $this->site;
        }

        $this->site = DB::getInstance()->getSite();
        return intval($this->site);
    }

    public function restartServer()
    {
        system("sudo /etc/init.d/apache2 reload");
    }

}
