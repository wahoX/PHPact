<?php
error_reporting(E_ALL ^ E_NOTICE);

function error_handler($code, $message, $file, $line)
{
/*
    echo "<hr /><strong>Code:</strong> ".$code."
	<strong>Line:</strong> ".$line."
	<strong>File:</strong> ".$file."
	<strong>Message:</strong> ".$message;
*/
    if ($code == 0) return;
    if ($code == 2) return;
    if ($code == 8) return;
    if ($code == 2048) return;
    if (0 == error_reporting())
    {
        return;
    }
    echo $message.":".$code.":".$file.":".$line;
    throw new ErrorException($message, 0, $code, $file, $line);
}

function exception_handler($e) {
	$output = ob_get_clean();
  print_r($e);
  echo "
  <b>Es ist ein Fehler aufgetreten:</b>
  <br />" . $e->getMessage(). " - in ".$e->getFile()." - Zeile ".$e->getLine()." (Code: ".$e->getCode().")<br /><br />
  <b>Trace:</b><hr />";
  foreach ($e->getTrace() AS $id => $t) {
	#if ($id == 0) continue;
	#print_r($t);
	  echo "Datei: ".$t["file"]." - Zeile: ".$t["line"]."<hr />";
  }
  echo "vorheriger Output: ".nl2br($output);
  die;
}



$now = new \DateTime();
$mwst_satz = 19;
if ($now->format("Y-m-d") >= "2020-07-01" && $now->format("Y-m-d") <= "2020-12-31") {
  $mwst_satz = 16;
}
define("MWST_SATZ", $mwst_satz);


set_exception_handler('exception_handler');
set_error_handler("error_handler");

require_once("lib/core/corefunctions.php");


// Cookie-Domain definieren
$domain = "";
$host = $_SERVER["HTTP_HOST"];
define ("COOKIE_DOMAIN", $host);

// Session starten
ini_set("session.cookie_domain", COOKIE_DOMAIN);
ini_set("session.cookie_httponly", true);
ini_set("session.gc_maxlifetime",3600);
ini_set("session.gc_probability",1);
ini_set("session.gc_divisor",1);
ini_set("session.use_trans_sid", "on");

require_once("lib/extern/spyc.php");
if (file_exists("config/config.ini")) {
	$yaml1 = SPYC::YAMLLoad('config/config.ini');
	if (is_array($yaml1["Constants"])) foreach($yaml1["Constants"] AS $k => $v) { if (!defined($k)) define($k, $v); }
}
if (!defined("FRAMEWORK_DIR")) define("FRAMEWORK_DIR", $_SERVER["DOCUMENT_ROOT"]."/../framework/");
if (!defined("VENDOR_DIR")) define("VENDOR_DIR", $_SERVER["DOCUMENT_ROOT"]."/../vendor/");
if (file_exists(FRAMEWORK_DIR . "config/config.ini")) {
	$yaml2 = SPYC::YAMLLoad(FRAMEWORK_DIR . 'config/config.ini');
	if (is_array($yaml2["Constants"])) foreach($yaml2["Constants"] AS $k => $v) { if (!defined($k)) define($k, $v); }
}
if (file_exists(VENDOR_DIR."autoload.php")) require_once(VENDOR_DIR."autoload.php");

session_name("PHPSESSID");
session_cache_expire(180);
$sid = $_COOKIE["PHPSESSID"];
if (!$sid) {
	$sid = $_REQUEST["PHPSESSID"];
	if ($sid) session_id($sid);
}

if (isset($_GET["mollie_sid"])) {
  $sid = $_GET["mollie_sid"];
  session_id($sid);
}

session_start();
\Application::getInstance()->init();
$sid = session_id();
$_SESSION["PHPSESSID"] = $sid;


require_once("lib/extern/spyc.php");

if (isset($yaml2["Aliases"]) && is_array($yaml2["Aliases"])) foreach($yaml2["Aliases"] AS $k => $v) \Application::getInstance()->addAlias($k, $v);
if (isset($yaml1["Aliases"]) && is_array($yaml1["Aliases"])) foreach($yaml1["Aliases"] AS $k => $v) \Application::getInstance()->addAlias($k, $v);

$request = Request::getInstance();


if ($_SESSION["OWNER"] < 1) $_SESSION["OWNER"] = Application::getInstance()->getOwner();

$owners = $_SESSION["OWNERS"];
if (!is_array($owners)) $owners = array();
$owners[$_SERVER["SERVER_NAME"]] = Application::getInstance()->getOwner();
$_SESSION["OWNERS"] = $owners;



$IncludeDir = str_replace(";", ":", ini_get("include_path"));
define("INCLUDE_DIR", ".:" . FRAMEWORK_DIR);

$IncludeDir = explode(":", $IncludeDir);
foreach($IncludeDir AS $dir) echo Application::getInstance()->addIncludeDir($dir);

$res = "res_".date("ymdH", time())."/";


header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache"); // HTTP/1.0
header("Content-Type: text/html; charset=UTF-8");

Application::getInstance()->setEnv(ENV);
$user = Application::getInstance()->getUser();
if (!$user->isLoggedIn()) Application::getInstance()->autologin();
