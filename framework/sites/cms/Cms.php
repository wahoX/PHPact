<?php
namespace Sites;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Cms extends \Controller
{
	private $field_title;
	private $field_content;
	
	public function __construct($template = "", $sub = "")
	{
		parent::__construct($template, $sub);
		$this->app->registerCSS("res/css/content.css");

		switch ($_SESSION["language"]) {
			case "en" :
				$this->field_title = "title_en";
				$this->field_content = "content_en";
				break;
			case "cz" :
				$this->field_title = "title_cz";
				$this->field_content = "content_cz";
				break;
			default :
				$this->field_title = "title";
				$this->field_content = "content";
				break;
		}
		
	}
	
	public function indexAction()
	{
		$url = $this->request->args[0];
		if (!$url) {
			$navi = $this->db->getCmsMenu();
			$url = reset($navi);
			$url = $url["url"];
		}
		$site = $this->db->getCmsSite($url);
		if (!$site) {
			$this->app->forward("oops");
		}
		
		$user = $this->app->getUser();
		$owner = $this->app->getOwner();
		$this->app->registerCSS("res/css/content.css");
		
		// Javascript und CSS laden, wenn man im Bearbeiten-Modus ist
		if ($this->app->adminmode())
		{
			$this->app->registerCSS("res/css/form.css");
			$this->app->registerCSS("res/css/admin.css");
			$this->app->registerJS("res/js/admin.js");
			$this->app->registerJS("res/js/jquery/jquery.form.js");
			$this->app->registerJS("res/js/ckeditor/ckeditor.js");
			$this->app->registerJS("res/js/ckeditor/adapters/jquery.js");
			$this->app->registerOnload("
$('.content_item').hover(
  function () {
//    $(this).find('.admin').fadeIn();
    $(this).find('.admin').css({'display' : 'block'});
    $(this).css({'background' : '#e9e9e9'});
  },
  function () {
//    $(this).find('.admin').fadeOut();
    $(this).find('.admin').css({'display' : 'none'});
    $(this).css({'background' : 'transparent'});
  }
);

getEditor('new_text', ".$site["id"].");
getEditor('new_preview', ".$site["id"].");
getEditor('new_download', ".$site["id"].");
getEditor('new_picture', ".$site["id"].");
", true);

			if (!$news) $this->app->registerOnload("initSortableContent(".$site["id"].");", true);

			$edit = new \Controller();
			$edit->setTemplate($this->parseTag("[EDIT]"));
			$edit->parse("URL", $url);
			$this->EDIT = $edit->render();

			if ($news) $tplToolbox = $this->getSubtemplate("TOOLBOX_NEWS");
			else $tplToolbox = $this->getSubtemplate("TOOLBOX");
			$this->TOOLBOX = $tplToolbox->render();

		}
		
		// HTML-Header-Infos schreiben
		$this->header->TITLE = $site["title"];
		$this->header->KEYWORDS = $site["keywords"];
		$this->header->DESCRIPTION = $site["description"];
		$tmp = $this->getTemplate();
		preg_replace("|<%[YAML]%>(.*?)<%[/YAML]%>|is", "<%[YAML]%>".$this->header->render()."<%[/YAML]%>", $tmp);
		$this->setTemplate($tmp);

		// Subtemplates laden
		$tplContent = $this->getSubTemplate("CONTENT_ITEM");
		$tplPreview = $this->getSubTemplate("PREVIEW_ITEM");
		$tplPicture = $this->getSubTemplate("PICTURE_ITEM");
		$tplPictures = $this->getSubTemplate("PICTURES_WRAPPER");
		// Wenn Bearbeiten-Modus, dann anderer Wrapper, da sonst die Sortierung nicht funktioniert.
		if ($this->app->adminmode()) $tplPictures = $this->getSubTemplate("PICTURES_WRAPPER_ADMIN");
		
		$now = new \DateTime();
		
// DB Anfrage anpassen + Join products on products_id ...
		$content = $this->db->getCmsContent($site['id'], $news);
		$last_type = $pictures = $tmp = "";
		$galerie = false;

		// Alle Inhalte durchlaufen
		foreach ($content AS $c) {
			// Wenn nicht im Bearbeiten-Modus: Prüfen, ob Inhalt gültig ist
			if (!$this->app->adminmode()) {
				$valid = true;
				if ($c["valid_from"] > '0000-00-00 00:00:00') {
					$from = new \DateTime($c["valid_from"]);
					if ($from->format('Y-m-d') > $now->format('Y-m-d')) $valid = false;
				}
				if ($c["valid_until"] > '0000-00-00 00:00:00') {
					$until = new \DateTime($c["valid_until"]);
					if ($until->format('Y-m-d') < $now->format('Y-m-d')) $valid = false;
				}
				if (!$valid) continue;
			}
			// Subtemplate nach Inhaltstyp wählen
			switch ($c["type"]) {
				case "preview" : $tmpTpl = $tplPreview; break;
				case "picture" : $galerie = true; $tmpTpl = $tplPicture; break;
//              $tplProduct muss definiert sein
                case "product" : $tmpTpl = $tplProduct; break;
				default : $tmpTpl = $tplContent; break;
			}
			$tmpTpl->ID = $c["content_id"];
			$tmpTpl->TEXT = $this->itemAction($c, $news);
			if ($c["type"] == "picture") $pictures .= $tmpTpl->render();
			
			// Wenn vorher Galeriebilder kamen und jetzt was anderes, dann den Wrapper für Galerie rendern
			if ($last_type == "picture" && $c["type"] != "picture") {
				$tplPictures->PICTURES = $pictures;
				$pictures = "";
				$tmp.=$tplPictures->render();
				$tplPictures->resetParser();
			}


			$last_type = $c["type"];
			// Inhalt rendern (wenn kein Galeriebild)
			if ($c["type"] != "picture") $tmp .= $tmpTpl->render();
			$tmpTpl->resetParser();
			
		}
		
		// Falls noch Galeriebilder im temporären $pictures sind, dann jetzt rendern
		if ($pictures != "") {
			$tplPictures->PICTURES = $pictures;
			$tmp.=$tplPictures->render();
		}
		
		if ($tmp == "" && !$this->app->adminmode() && !$site["formular"])
		{
			$sub = $this->db->getFirstSubmenuItem($site['id']);
			if ($sub) $this->app->forward($sub);
		}
		
		$this->CONTENT = $tmp;
		
		if ($site["formular"] > 0) {
			$this->FORM = new \Block("Contact");
		}
		
		// Wenn Seite Galeriebilder enthält, dann Fancybox laden.
		if ($galerie) {
			$this->GALLERYCONTROL = $this->getSubtemplate("GALLERYCONTROL")->render();
			$this->CONTENT .= '<script src="/res2/js/jquery.blueimp-gallery.min.js"></script>';
			$this->app->registerCss("res/css/blueimp-gallery.min.css");
		}
	}

	/**
	 * Gibt ein Inhalts-Item zurück
	 * Wenn Ajax, dann wird es ausgegeben, ansonsten als return-Wert.
	 *
	 * @param mixed $id ID des Inhaltspunktes ODER Assoziatives Array (aus DB)
	 *
	 * @return gerenderter Inhaltspunkt
	 **/
	public function itemAction($id=0, $news=false)
	{
		var_dump($news);

		if ($id == 0) $id = $this->request->args[0];
		if (is_numeric($id)) {
			$content = $this->db->getCmsItem($id);
		}
		else {
			$content = $id;
		}

		$tplMain = new \Controller(__DIR__ . "/item.html");
		
		if($content["type"] == "preview") {
			$tpl = $tplMain->getSubTemplate("CMS_VORSCHAU");
			$tpl->IMAGE = $content["preview_image"];
			if($content["pos"] % 2 == 0) { //Jeweils die vordere Box kriegt ein Margin
				$tpl->MARGIN = "margin-right:9px;";		
			}
			else { //Jeweils die hintere Box kriegt ein Clear
				$tpl->CLEAR = "<div class='clear'></div>";		
			}	
			
			$menu = $this->db->getMenuByID($content["preview_link"]);
			$m = $this->app->getModule($menu["module"]);
			$tpl->LINK = $m->getUrl($menu["url"]);
                        if ($content["preview_anchor"] > 0)
                        $tpl->ANKER = "#content_".$content["preview_anchor"]; 
		}	
		else if($content["type"] == "picture") {
			$tpl = $tplMain->getSubTemplate("CMS_PICTURE");
			
		}

		else if($content["type"] == "download") {
			$tpl = $tplMain->getSubTemplate("CMS_DOWNLOAD");
			$tpl->FILENAME = $content["download_link"];
			$tpl->NAME = $content["download_name"];
			$size = $content["download_size"];
			$prefix = "Byte";
			if (($size / 1024) > 1) {
				$size = $size / 1024;
				$prefix = "kB";
			}
			if (($size / 1024) > 1) {
				$size = $size / 1024;
				$prefix = "MB";
			}
			if (($size / 1024) > 1) {
				$size = $size / 1024;
				$prefix = "GB";
			}
			$tpl->SIZE = number_format($size, 1, ",", ".")." ".$prefix;
			if (trim($content[$this->field_content]) != "") $content[$this->field_content].="<br />";
			$ext = strtolower(array_pop(explode('.', $content["download_name"])));
			if (in_array($ext, array("avi", "bat", "bmp", "bz2", "ccd", "com", "cue", "deb", "doc", "docx", "exe", "fla", "flv", "fon", "gif", "gz", "htm", "html", "iso", "jpeg", "jpg", "mds", "mdx", "mid", "midi", "mkv", "mov", "mp3", "mpeg", "mpg", "nrg", "ogg", "pdf", "png", "ppt", "pptx", "qt", "rpm", "rtf", "sh", "swf", "tgz", "tif", "tiff", "torrent", "ttf", "txt", "wav", "wma", "xls", "xlsx", "zip", "", "", "", "", "", "ini")))
				$tpl->FILETYPE = $ext;
			else $tpl->FILETYPE = "unknown";
		}
		else {
			$tpl = $tplMain->getSubTemplate("CMS_NORMAL");	
		}
		
		if ($news) {
			$tplDate = $tplMain->getSubtemplate("DATE");
			$date = new \DateTime($content["zeit"]);
			$tplDate->DATE_CREATED = $date->format("d.m.Y");
			$tpl->DATE_CREATED = $tplDate->render();
			$tplDate->resetParser();
		}

		if ($this->app->adminmode())
		{
			$tplAdmin = new \Controller();
			$tplAdmin->setTemplate($tplMain->parseTag("[CONTENT_ADMIN]"));
			$tplAdmin->ID = $content["content_id"];
			$tpl->CONTENT_ADMIN = $tplAdmin->render();
		}

		$title = $content[$this->field_title];
		if ($title == "") $title = $content["title"];
		if ($title != "") {
			$tpl->HEADER = "<h2>".$title."</h2>";
		} else {
			$tpl->HEADER = "";
		}
		
		$text = $content[$this->field_content];
		if ($text == "") $text = $content["content"];
		$tpl->TEXT = $text;
		
		$id = $content["content_id"];
		if ($id < 1) $id = $content["id"];
		
		$tpl->ID = $id;
		$tpl->OWNER = $this->app->getOwner();
		
		$result = $tpl->render();
		$this->app->addAjaxContent($result);
		return $result;
	}
    

	/**
	 * Lädt das Formular zum Ändern eines Inhaltspunktes und gibt dieses für Ajax aus
	 **/
	public function getformAction()
	{
		if (!$this->app->adminmode()) return false;
		$id = $this->request->args[0];
		$mid = $this->request->get["mid"];
		$content = $this->db->getCmsItem($id);
		
		
		$type = $content["type"];
		if (stristr($id, "new_")) {
			$new = true;
			$tmp = explode("_", $id);
			$type = $tmp[1];
			
			$site = $this->db->getCmsSite($mid, true);

			// Wenn $news == true, dann hat der Menüpunkt das Modul "News"
			// Nur interessant, wenn neuer Inhaltspunkt...
			$news = ($site["module"] == "news") ? true : false;
		}
		
		switch($type) {
			case "download" :
				$this->setTemplate($this->parseTag("[FORM_DOWNLOAD]"));
				break;
			case "picture" :
				$this->setTemplate($this->parseTag("[FORM_PICTURE]"));
				break;
			case "preview" :
				$this->setTemplate($this->parseTag("[FORM_PREVIEW]"));
				$tplOption = $this->getSubtemplate("PREVIEW_MENU_OPTION");
				$menu = $this->db->getCmsMenu();
				$options = "";
				reset($menu);
				foreach ($menu AS $m) {
					$tplOption->MENU_ID = $m["id"];
					$name = "";
					for ($i=0; $i < $m["level"]; $i++) $name .= "- ";
					$name .= $m["name"];
					$tplOption->NAME = $name;
					if ($m["id"] == $content["preview_link"]) $tplOption->SELECTED = " selected='selected'";
					$options .= $tplOption->render();
					$tplOption->resetParser();
					$this->OPTIONS = $options;
                                        $this->ANKER_VALUE = $content["preview_anchor"];
				}
				break;
			default :
				$this->setTemplate($this->parseTag("[FORM_TEXT]"));
				break;
		}

		$title = $content[$this->field_title];
		if ($title == "") $title = $content["title"];
		$title = str_replace("\"", "&quot;", $title);
		$this->TITLE = $title;//(\Utils::str_encode($title, "ISO-8859-1"));

		$text = $content[$this->field_content];
		if ($text == "") $text = $content["content"];
		$this->TEXT = $text;
		
		$beginn = $content["valid_from"];
		if ($beginn > '0000-00-00 00:00:00') {
			$beginn = new \DateTime($beginn);
			$this->BEGINN_VALUE = $beginn->format("d.m.Y");
		}
		$ende = $content["valid_until"];
		if ($ende > '0000-00-00 00:00:00') {
			$ende = new \DateTime($ende);
			$this->ENDE_VALUE = $ende->format("d.m.Y");
		}

		if ($news && $new) {
			$date = new \DateTime();
			$date->add(new \DateInterval('P1M'));
			$this->ENDE_VALUE = $date->format("d.m.Y");
		}

		$this->ID = $id;
		$this->MID = $mid;
		
		if ($new) $this->ACTION = "hinzufügen";
		else $this->ACTION = "bearbeiten";
		
		
		$return = $this->render();
		if (!$new) $return = "<div class='content_box'>".$return."</div>";
		$this->app->addAjaxContent($return);
	}

	/**
	 * Speichert einen Inhaltspunkt ab.
	 * Wenn Ajax, dann gibt es den Inhaltspunkt aus.
	 **/
	public function saveAction()
	{
		if (!$this->app->adminmode()) return false;
		
		$id = $this->request->post["id"];
		$text = $this->request->post["content"];
		$title = $this->request->post["title"];
		$menu_id = $this->request->post["menu_id"];
		$type = $this->request->post["type"];
		$valid_from = $this->request->post["beginn"];
		$valid_until = $this->request->post["ende"];
		
		
		
		$preview_image = $preview_link = $download_link = $download_type = $download_size = "";
		
		if ($type == "") $type="text";
		
		if ($type == "preview") {
			$x1 = $this->request->post["x1"];
			$x2 = $this->request->post["x2"];
			$y1 = $this->request->post["y1"];
			$y2 = $this->request->post["y2"];
			$h = $this->request->post["h"];
			$w = $this->request->post["w"];
			$hf = $this->request->post["hf"];
			$wf = $this->request->post["wf"];
			$filename = $this->request->post["image"];
			$maxwidth = 90;
			
			if ($filename) {
				require_once(FRAMEWORK_DIR . "sites/imageupload/Imageupload.php");
				$Imageupload = new \Sites\Imageupload();
				
				$img = $Imageupload->createImage($x1, $x2, $y1, $y2, $h, $w, $hf, $wf, $filename, $maxwidth);
				if (!file_exists($Imageupload->avatarDir."/preview")) mkdir ($Imageupload->avatarDir."/preview");
				$finalname = "/preview/preview_".time().".jpg";
				imagejpeg($img, $Imageupload->avatarDir.$finalname, 90);

				$preview_image = "res2/uploads/".$this->app->getOwner()."/images".$finalname;
			}
			
			$preview_link = $this->request->post["preview_link"];
			$preview_anchor = $this->request->post["anker"];
		}

		if ($type == "download") {
			$file = $_FILES["upload"];
			$dir = FRAMEWORK_DIR . "/res/uploads/".$this->app->getOwner()."/";
			if (!file_exists($dir)) mkdir ($dir);
			$dir.= "files/";
			if (!file_exists($dir)) mkdir ($dir);
			$finalname = md5(microtime());
			
			if (move_uploaded_file($file["tmp_name"],$dir.$finalname)) {
				$download_name = basename($file['name']);
				$download_link = $finalname;
				$download_type = \Utils::getMimeType($file["name"]);
				$download_size = filesize($dir.$finalname);
			}
		}
		
		if ($type == "picture") {
			$file = $_FILES["upload"];
			$tmp_name = md5(microtime());
			$small = $tmp_name."_k.jpg";
			$large = $tmp_name.".jpg";
			$dir = FRAMEWORK_DIR . "/res/uploads/".$this->app->getOwner()."/";
			if (!file_exists($dir)) mkdir ($dir);
			$dir.= "images/";
			if (!file_exists($dir)) mkdir ($dir);
			
			require_once(FRAMEWORK_DIR . "sites/imageupload/Imageupload.php");
			$Imageupload = new \Sites\Imageupload();
			
			$success = $Imageupload->resizeImage(NULL, $file["tmp_name"], 120, $dir.$small, "both");
			if ($success) {
				$Imageupload->resizeImage(NULL, $file["tmp_name"], 1000, $dir.$large, "both");
			} else {
				$this->app->forward($this->request->referrer);
			}
		}
		//updateCmsItem()
		$url = $this->db->updateCmsItem($id, $title, $text, $menu_id, $type, $preview_image, $preview_anchor, $preview_link, $download_link, $download_type, $download_size, $download_name, $valid_from, $valid_until);
		if (stristr($id, "new_")) $this->app->addAjaxContent($url.":::::");
		$this->itemAction($url);
		
		if ($type == "picture") {
			rename($dir.$small, $dir.$url."_k.jpg");
			rename($dir.$large, $dir.$url.".jpg");
		}

		$this->app->forward($this->request->referrer);
	}
	
	public function downloadAction() {
		$this->app->disableRender();
		$link = $this->request->args[0];
		$download = $this->db->getDownload($link);
		if (!$download) $this->app->forward("404");
		$this->app->disableRender();
		
		$dir = FRAMEWORK_DIR . "res/uploads/".$this->app->getOwner()."/files/";
		$file = file_get_contents($dir.$link);

		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: private");
		header("Content-Type: ".$download["download_type"]);
		header("Content-Disposition: atachment; filename=\"".$download["download_name"]."\"");
		header("Content-Description: File Transfer");
		header("Content-Length: ".$download["download_size"]);
		
		$this->app->addAjaxContent($file);
	}
	
	public function saveContentOrderAction()
	{
		if (!$this->app->adminmode()) return false;
		$order = $this->request->post["content"];
		$menu_id = intval($this->request->args[0]);
		$this->db->saveCmsOrder($menu_id, $order);
	}
	
	public function deleteAction()
	{
		if (!$this->app->adminmode()) return false;
		$id = intval($this->request->args[0]);
		$this->db->deleteCmsItem($id);
		
	}

	/* veraltet */
	public function editAction()
	{
		if (!$this->app->adminmode()) return false;
		$this->app->registerCSS(".cke_1 { margin: 0 -20px; }", true);
		$owner = $this->app->getOwner();
		$user = $this->app->getUser();
		$url = $this->request->args[0];
		$inhalt = $this->db->getCmsSite($url);
		if ($user->id == $owner || $this->acl->checkRight(S_ADMIN))
		{
			if ($this->request->post["send"]) {
				$success = $this->db->updateNaviItem($url, $this->request->post);
				if ($success) $this->app->addSuccess("Die Änderungen wurden gespeichert.");
				else $this->app->addHint("Es wurden keine Änderungen gespeichert.");
				$forward = $this->request->post["forward"];
				if (!$forward) $forward = $url;
				$this->app->forward($forward);
			} else {
				print_r($this);
				$this->app->registerJS("res/js/ckeditor/ckeditor.js");
				$this->EDITOR1_VALUE = htmlentities(utf8_decode($inhalt["content"]));
				$this->MENUNAME_VALUE = htmlentities(utf8_decode($inhalt["name"]));
				$this->TITLE_VALUE = htmlentities(utf8_decode($inhalt["title"]));
				$this->KEYWORDS_VALUE = htmlentities(utf8_decode($inhalt["keywords"]));
				$this->DESCRIPTION_VALUE = htmlentities(utf8_decode($inhalt["description"]));
				$this->SHOW_FORM_CHECKED = ($inhalt["formular"]) ? " checked='checked'" : "";
				$this->FORWARD_VALUE = $this->request->referrer;
			}
		} else {
			$this->app->addError("Sie haben kein Recht, diese Seite zu bearbeiten!");
			$this->setTemplate("");
		}
	}
	
	public function deletemenuAction()
	{
		if (!$this->app->adminmode()) return false;
		$owner = $this->app->getOwner();
		$user = $this->app->getUser();
		if ($user->id == $owner || $this->acl->checkRight(S_ADMIN)) {
			$id = $this->request->args[0];
			$this->db->deleteNaviItem($id);
			$this->app->addSuccess("Der Menüpunkt wurde gelöscht.");
			$this->app->forward($this->request->referrer);
		}
	}
	
	public function saveseoAction() {
		$post = $this->request->post;
		$id = $post["item_id"];
		$url = $post["seo_url"];
		if (!$this->checkurlAction($url)) {
			return false;
		}
		$title = $post["seo_title"];
		$description = $post["seo_description"];
		$keywords = $post["seo_keywords"];
		$this->db->saveSeo($id, $url, $title, $description, $keywords);
	}
	
	public function checkurlAction($checkurl = "") {
		if ($checkurl == "") {
			$checkurl = $this->request->post["seo_url"];
		}
		if ($checkurl == "") {
			$checkurl = $this->request->get["fieldValue"];
		}

		if ($checkurl == "") {
			$this->app->addAjaxContent('["seo_url",false]');
			return false;
		}

		$cmsurl = $this->request->referrer;
		$cmsurl = str_replace(array("http://", "https://", $_SERVER["HTTP_HOST"], $this->referrer->root), array("", "", "", ""), $cmsurl);
		$cmsurl = str_replace("/", "", $cmsurl);
		$site = $this->db->getCmsSite($cmsurl);
		$cmsurl = $site["url"];

		if ($checkurl == $cmsurl) {
			$this->app->addAjaxContent('["seo_url",true]');
			return true;
		}

		$check = $this->db->getCmsIdByUrl($checkurl);
		if (intval($check > 0)) {
			$this->app->addAjaxContent('["seo_url",false]');
			return false;
		} else {
			$this->app->addAjaxContent('["seo_url",true]');
			return true;
		}
	}
	
}

?>
