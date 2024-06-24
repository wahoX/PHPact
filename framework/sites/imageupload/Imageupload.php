<?php
/**
 * \namespace \Sites
 */
namespace Sites;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
use \Controller as Controller;

class Imageupload extends \Controller
{
	public $avatarDir;
	private $tmpDir;
	private $owner;
	
	public function __construct($template = "", $tag="") {
		parent::__construct($template, $tag);
		$this->owner = $this->app->getOwner();
		$this->avatarDir = FRAMEWORK_DIR . "res/uploads/".$this->owner."/";
		if (!file_exists($this->avatarDir)) mkdir ($this->avatarDir);
		$this->avatarDir .= "images/";
		if (!file_exists($this->avatarDir)) mkdir ($this->avatarDir);
		#$this->app->addAjaxContent($this->avatarDir);

		$this->tmpDir = FRAMEWORK_DIR . "res/uploads/tmp/";
		if (!file_exists($this->tmpDir)) mkdir ($this->tmpDir);
	}
	
	
    public function deleteHeaderImageAction() {
		$id = intval($this->request->args[0]);
		$menu = $this->db->getMenuByID($id);
		if ($menu["site_id"] != $this->app->getSite()) $this->app->forward("404");
		if (!$this->app->adminmode()) $this->app->forward("404");
		$this->db->deleteNaviImage($id);
		$url = str_replace("res2/", "res/", $menu["header_image"]);
		unlink(FRAMEWORK_DIR."/".$url);
		
		$this->app->addSuccess("Das Bild wurde entfernt.");
		$this->app->forward($this->request->referer);
	}

    public function kcfinderimageAction() {
        $url = substr($this->request->history[0], 1, strlen($this->request->history[0])) ;
        $post = $this->request->post;
        $menu = $this->db->getCmsSite($url);
        $menu_id = $menu["id"];
        $img_url =substr($post["url"], 1, strlen($post["url"]));
        $this->db->saveNaviImage($menu_id, $img_url);
    }
    
    
	public function imageAction()
	{
		$maxwidth = intval($this->request->args[0]);
		if ($maxwidth < 1) $maxwidth = $this->app->getSettings("header_width");
		
		$maxheight = intval($this->request->args[1]);
		if ($maxheight < 1) $maxheight = $this->app->getSettings("header_height");

		$ratio = $maxwidth / $maxheight;
		
		$multi2 = 500 / $maxwidth;
		
		$f = $_FILES["userimg"];
		if ($f['tmp_name'])
		{
			if (!getimagesize($f['tmp_name'])) {
				$this->app->addAjaxContent("Die Datei ist keine Bilddatei.");
				return;
			}

			$tmpname = $this->tmpDir.$this->owner."_".$f["name"];
			$filename = md5(microtime()).".jpg";
			$finalname = $this->tmpDir.$filename;

			if (move_uploaded_file($f['tmp_name'], $tmpname))
			{
				$size = $this->resizeImage($f, $tmpname, $maxwidth, $finalname);
				$tmpratio = $size[0] / $size[1];

				$multi = 500 / $size[0];
				
				if ($tmpratio > $ratio) {
					$tmp = floor(500/($tmpratio / $ratio));
					$multi2 = $tmp/$maxwidth;
				}
				$center_x = ($multi * $size[0]) / 2;
				$center_y = ($multi * $size[1]) / 2;
				
				$tmpwidth = $multi2 * $maxwidth;
				$tmpheight = $multi2 * $maxheight;
				
				$start_x = ceil($center_x - ($tmpwidth / 2));
				$end_x = floor($center_x + ($tmpwidth / 2));
				$start_y = ceil($center_y - ($tmpheight / 2));
				$end_y = floor($center_y + ($tmpheight / 2));
				
				@unlink($tmpname);
				$this->OWNER = $this->owner;
				$this->FILENAME = $filename;
				$this->WIDTH = $size[0];
				$this->HEIGHT = $size[1];
				$this->FINAL_WIDTH = $maxwidth;
				$this->FINAL_HEIGHT = $maxheight;
				$this->START_X = $start_x;
				$this->START_Y = $start_y;
				$this->END_X = $end_x;
				$this->END_Y = $end_y;
				if ($this->request->args[2] != "inline") {
					$tplStart = $this->getSubtemplate("FORM_START");
					$this->FORM_START = $tplStart->render();
					$tplEnd = $this->getSubtemplate("FORM_END");
					$tplEnd->MENU_ID = $this->request->post["menu_id"];
					$this->FORM_END = $tplEnd->render();
				}
				$this->app->addAjaxContent($this->render());
			}
		}
	}
	
	public function logoAction() {

		$f = $_FILES["userimg_logo"];
		if ($f['tmp_name'])
		{
			if (!getimagesize($f['tmp_name'])) {
				$this->app->addAjaxContent("Die Datei ist keine Bilddatei.");
				return;
			}
		
			$tmpname = $this->tmpDir.$this->owner."_".$f["name"];
			$filename = "logo_".time().".jpg";
			$finalname = $this->avatarDir.$filename;

			if (move_uploaded_file($f['tmp_name'], $tmpname))
			{
				$this->resizeImage($f, $tmpname, "85", $finalname, "height");
				@unlink ($tmpname);
				$filename = "res2/uploads/".$this->owner."/images/".$filename;
				$this->db->saveSetting("logo", $filename);
				$this->app->addAjaxContent($filename);
			}
		
		}
	}


	public function faviconAction() {
		$f = $_FILES["userimg_favicon"];
		if ($f['tmp_name'])
		{
			if (!getimagesize($f['tmp_name'])) {
				$this->app->addAjaxContent("Die Datei ist keine Bilddatei.");
				return;
			}

			$tmpname = $this->tmpDir.$this->owner."_".$f["name"];
			$filename = "favicon_".time().".ico";
			$finalname = $this->avatarDir.$filename;

			if (move_uploaded_file($f['tmp_name'], $tmpname))
			{
	
				$size = getimagesize($tmpname);
				switch($size["mime"]) {
					case "image/png" :
						$source = imagecreatefrompng($tmpname);
						break;
					case "image/jpeg" :
					case "image/jpg" :
						$source = imagecreatefromjpeg($tmpname);
						break;
					case "image/gif" :
						$source = imagecreatefromgif($tmpname);
						break;
					default :
						die("Keine gültige Datei.");
				}

				$sizes = array(16, 32, 48);
				$files = array();
			
				foreach ($sizes AS $s) {
					$file = $this->tmpDir."favicon_".$s.".png";
					$img = imagecreatetruecolor($s, $s);
					$this->setTransparency($img);
					imagecopyresampled($img, $source, 0, 0, 0, 0, $s, $s, $size[0], $size[1]);
					imagepng($img, $file);
					$files[] = $file;
				}
			
				shell_exec('icotool -c -o '.$finalname.' '.implode(" ", $files));


				foreach ($files AS $f) {
					#@unlink($f);
				}

				@unlink ($tmpname);
				$filename = "res2/uploads/".$this->owner."/images/".$filename;
				$this->db->saveSetting("favicon", $filename);
				$this->app->addAjaxContent($filename);
			}
		
		}
	}


	public function createimageAction()
	{
		$finalname = "header/header_".time().".jpg";
		
		$dir = $this->avatarDir . "header/";
		if (!file_exists($dir)) mkdir ($dir);

		$large = $this->createImage();
		imagejpeg($large, $this->avatarDir.$finalname, 90);

		$this->app->addAjaxContent($finalname);
		
		@unlink ($this->tmpDir.$this->request->post["image"]);
		if ($this->request->post["menu_id"]) {
			$this->db->saveNaviImage($this->request->post["menu_id"], "res2/uploads/".$this->owner."/images/".$finalname);
		} else  {
			$this->db->saveSetting("contentheader_image", "res/uploads/".$this->owner."/images/".$finalname);
		}
	}


	public function createImage($x1=0, $x2=0, $y1=0, $y2=0, $h=0, $w=0, $hf=0, $wf=0, $filename="", $maxwidth=0)
	{
		if (!$x1) $x1 = $this->request->post["x1"];
		if (!$x2) $x2 = $this->request->post["x2"];
		if (!$y1) $y1 = $this->request->post["y1"];
		if (!$y2) $y2 = $this->request->post["y2"];
		if (!$h) $h = $this->request->post["h"];
		if (!$w) $w = $this->request->post["w"];
		if (!$hf) $hf = $this->request->post["hf"]; //Finale Breite
		if (!$wf) $wf = $this->request->post["wf"]; //Finale Höhe
		if (!$filename) $filename = $this->tmpDir.$this->request->post["image"];
		else $filename = $this->tmpDir.$filename;
		if (!$maxwidth) $maxwidth = $this->app->getSettings("header_width");

		print_r($this->request->post);

		$size = getimagesize($filename);
		if (!$size) die();
		
		$bg = imagecreatetruecolor ($wf, $hf);
		$white = imagecolorallocate($bg, 255, 255, 255);
		imagefilledrectangle($bg, 0, 0, $wf, $hf, $white);

		switch ($size["mime"])
		{
			case "image/x-png" :
			case "image/png" :
				$avatar = imagecreatefrompng($filename);
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				$avatar = imagecreatefromjpeg($filename);
				break;
			case "image/gif" :
				$avatar = imagecreatefromgif($filename);
				break;
			default :
				$this->app->addError("Das Bild hat ein ungültiges Format.");
				@unlink($tmpname);
				return false;
		}
		$multi = $size[0] / 500;
		$multi2 = 500 / $maxwidth;
		$x1 = floor($x1 * $multi);
		$y1 = floor($y1 * $multi);
		$w = floor($w * $multi);
		$h = floor($h * $multi);
		imagecopyresampled($bg, $avatar, 0, 0, $x1, $y1, $wf, $hf, $w, $h);


		return $bg;
	}
	
	
	private function setTransparency(&$img)
	{
		imagesavealpha($img, true);
		$trans_color = imagecolorallocatealpha($img, 255, 255, 255, 127);
		imagefill($img, 0, 0, $trans_color);
	} 

	/**
	 * Werte für $resize:
	 *
	 * false: keine Änderung der Größe, nur Neuberechnung und Speichern des Bildes.
	 * "width": Nur die Breite ist interessant (maximale Breite)
	 * "height": Nur die Höhe ist interessant (maximale Höhe)
	 * "both": maximale Kantenlänge (egal ob Breite oder Höhe)
	 **/
	public function resizeImage($f = "veraltet", $image, $max = 200, $finalname, $resize=false)
	{
		ini_set('memory_limit', '128M');
		$size = getimagesize($image);
		if (!$size) return false;
		
		switch ($size["mime"])
		{
			case "image/x-png" :
			case "image/png" :
				$avatar = imagecreatefrompng($image);
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				$avatar = imagecreatefromjpeg($image);
				break;
			case "image/gif" :
				$avatar = imagecreatefromgif($image);
				break;
			default :
				$this->app->addError("Das Bild hat ein ungültiges Format.");
				return false;
		}

		$w = $thumbwidth = $size[0];
		$h = $thumbheight = $size[1];
		
		if ($resize == "width" && $w > $max) {
			$w = $max;
			$h = $thumbheight * $max / $thumbwidth;
		}
		if ($resize == "height" && $h > $max) {
			$h = $max;
			$w = $thumbwidth * $max / $thumbheight;
		}
		if ($resize == "both" && ($w > $max || $h > $max)) {
			if ($thumbwidth > $thumbheight) {
				$w = $max;
				$h = $thumbheight * $max / $thumbwidth;
			} else {
				$h = $max;
				$w = $thumbwidth * $max / $thumbheight;
			}
		}
		$thumbwidth = intval($w);
		$thumbheight = intval($h);

		$bg = imagecreatetruecolor ($thumbwidth , $thumbheight);
		$white = imagecolorallocate($bg, 255, 255, 255);
		imagefilledrectangle($bg, 0, 0, $thumbwidth , $thumbheight, $white);

		imagecopyresampled($bg, $avatar, 0, 0, 0, 0, $thumbwidth, $thumbheight, $size[0], $size[1]);
		
		imagejpeg($bg, $finalname, 90);
		
		return array($thumbwidth, $thumbheight);
	}

	


	private function upload()
	{
		$f = $_FILES["userimg"];
		if ($f['tmp_name'])
		{
			if (!getimagesize($f['tmp_name'])) {
				$this->app->addError("Die Datei ist keine Bilddatei.");
				$this->app->forward($this->request->referrer);
			}
			$tmpname = $this->avatarDir."tmp_".$this->user->id."_".$f["name"];
			$filename = md5($user->id.microtime()).".jpg";
			$finalname = $this->avatarDir.$filename;
			$final2name = $this->avatarDir."small_".$filename;
			if (move_uploaded_file($f['tmp_name'], $tmpname))
			{
#				$this->createAvatar($f, $tmpname, 128, $finalname);
				
				@unlink($tmpname);
				$this->app->addSuccess("Das Bild wurde hochgeladen.");
			}
			return $filename;
		}
	}

	public function branchenImage($image, $max = 300, $dir, $finalname) {

		$size = getimagesize($image);
		if (!$size) return false;
		
		switch ($size["mime"])
		{
			case "image/x-png" :
			case "image/png" :
				$avatar = imagecreatefrompng($image);
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				$avatar = imagecreatefromjpeg($image);
				break;
			case "image/gif" :
				$avatar = imagecreatefromgif($image);
				break;
			default :
				$this->app->addError("Das Logo hat ein ungültiges Format.");
				return false;
		}

        
        $w = $thumbwidth = $size[0];
        $h = $thumbheight = $size[1];

        if ($thumbwidth > $thumbheight) {
            $w = $max;
            $h = $thumbheight * $max / $thumbwidth;
        } else {
            $h = $max;
            $w = $thumbwidth * $max / $thumbheight;
        }

		$thumbwidth = intval($w);
		$thumbheight = intval($h);

		$bg = imagecreatetruecolor ($max , $max);

        $transparencyIndex = imagecolortransparent($avatar); 
        imagefill($bg, 0, 0, $transparencyIndex);
        imagecolortransparent($bg, $transparencyIndex);
		imagesavealpha($bg, true); // save alphablending setting (important)

        #$color = imagecolorallocatealpha($bg, 0, 255, 0, 127);
        #imagefill($bg, 0, 0, $color);
        #imagecolortransparent($bg, $color);
        
        $mar_top    = ($max - $thumbheight)/2;
        $mar_left   = ($max - $thumbwidth)/2;

		imagecopyresampled($bg, $avatar, $mar_left, $mar_top, 0, 0, $thumbwidth, $thumbheight, $size[0], $size[1]);
        
		$oldfile = $_SERVER["DOCUMENT_ROOT"] . "/res/cache/branchenlogos/200/" . str_replace(".jpg", "", $finalname) . ".png";
        @unlink($oldfile);
		$oldfile = $_SERVER["DOCUMENT_ROOT"] . "/res/cache/branchenlogos/80/" . str_replace(".jpg", "", $finalname) . ".png";
        @unlink($oldfile);

        imagepng($bg, $dir . $finalname);
		
		return array($thumbwidth, $thumbheight);

    }
	
	public function galleryPicture($img, $url, $finalname, $max = 600, $monochrome = false, $framework = true) {

		$size = getimagesize($img);
		if (!$size) return false;
		
		switch ($size["mime"])
		{
			case "image/x-png" :
			case "image/png" :
				$avatar = imagecreatefrompng($img);
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
                if ($monochrome == true) {
                    $avatar = $this->color_to_monochrome($img, $size["mime"]);
                }
                else {
                    $avatar = imagecreatefromjpeg($img);
                }
				break;
			case "image/gif" :
				$avatar = imagecreatefromgif($img);
				break;
			default :
				$this->app->addError("Das Logo hat ein ungültiges Format.");
				return false;
		}

        
        $w = $thumbwidth = $size[0];
        $h = $thumbheight = $size[1];

        if ($thumbwidth > $thumbheight) {
            $w = $max;
            $h = $thumbheight * $max / $thumbwidth;
        } else {
            $h = $max;
            $w = $thumbwidth * $max / $thumbheight;
        }

		$thumbwidth = intval($w);
		$thumbheight = intval($h);

		$bg = imagecreatetruecolor ($max , $max);

        $transparencyIndex = imagecolortransparent($avatar); 
        imagefill($bg, 0, 0, $transparencyIndex);
        imagecolortransparent($bg, $transparencyIndex);
		imagesavealpha($bg, true); // save alphablending setting (important)
        
        $mar_top    = ($max - $thumbheight)/2;
        $mar_left   = ($max - $thumbwidth)/2;

        
		imagecopyresampled($bg, $avatar, $mar_left, $mar_top, 0, 0, $thumbwidth, $thumbheight, $size[0], $size[1]);
        
        $dir = ($framework == true) ? FRAMEWORK_DIR : "";
        if ($monochrome == false) 
            imagepng($bg, $dir . str_replace("res2/", "res/", $url) . $finalname);
        else
            imagejpeg($bg, $dir . str_replace("res2/", "res/", $url) . $finalname);
        
		return array($thumbwidth, $thumbheight);

    }
	
	public function branchenPicture($image, $max = 300, $dir, $finalname) {

		$size = getimagesize($image);
		if (!$size) return false;
		
		switch ($size["mime"])
		{
			case "image/x-png" :
			case "image/png" :
				$avatar = imagecreatefrompng($image);
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				$avatar = imagecreatefromjpeg($image);
				break;
			case "image/gif" :
				$avatar = imagecreatefromgif($image);
				break;
			default :
				$this->app->addError("Das Bild hat ein ungültiges Format.");
				return false;
		}

        
        $w = $thumbwidth = $size[0];
        $h = $thumbheight = $size[1];

        if ($thumbwidth > $thumbheight) {
            $w = $max;
            $h = $thumbheight * $max / $thumbwidth;
        } else {
            $h = $max;
            $w = $thumbwidth * $max / $thumbheight;
        }

		$thumbwidth = intval($w);
		$thumbheight = intval($h);

		$bg = imagecreatetruecolor ($thumbwidth , $thumbheight);
		$transparencyIndex = imagecolortransparent($avatar); 
        imagefill($bg, 0, 0, $transparencyIndex);
        imagecolortransparent($bg, $transparencyIndex);
		imagesavealpha($bg, true); // save alphablending setting (important)
        
        $mar_top    = ($max - $thumbheight)/2;
        $mar_left   = ($max - $thumbwidth)/2;

		imagecopyresampled($bg, $avatar, 0, 0, 0, 0, $thumbwidth, $thumbheight, $size[0], $size[1]);

        imagepng($bg, $dir . $finalname);
		
		return array($thumbwidth, $thumbheight);

    }


	public function branchenBanner($image, $dir, $finalname) {

		$size = getimagesize($image);
		if (!$size) return false;
		
		switch ($size["mime"])
		{
			case "image/x-png" :
			case "image/png" :
				$avatar = imagecreatefrompng($image);
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				$avatar = imagecreatefromjpeg($image);
				break;
			case "image/gif" :
				$avatar = imagecreatefromgif($image);
				break;
			default :
				$this->app->addError("Das Banner hat ein ungültiges Format.");
				return false;
		}
		
		$thumbwidth = 130;
		$thumbheight = 80;

		$bg = imagecreatetruecolor ($thumbwidth , $thumbheight);
        $transparencyIndex = imagecolortransparent($avatar); 
        imagefill($bg, 0, 0, $transparencyIndex);
        imagecolortransparent($bg, $transparencyIndex);
		imagesavealpha($bg, true); // save alphablending setting (important)
        
		imagecopyresampled($bg, $avatar, 0, 0, 0, 0, $thumbwidth, $thumbheight, $size[0], $size[1]);

        imagepng($bg, $dir . $finalname);
		
		return array($thumbwidth, $thumbheight);

    }
    
    private function color_to_monochrome($file, $typ) {
        // The file you are grayscaling 
//        $file = 'yourfile.jpg'; 

        // This sets it to a .jpg, but you can change this to png or gif if that is what you are working with
//        header('Content-type: image/jpeg'); 

        // Get the dimensions
        list($width, $height) = getimagesize($file); 

        // Define our source image 
		switch ($typ)
		{
			case "image/x-png" :
			case "image/png" :
				$source = imagecreatefrompng($image);
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
                $source = imagecreatefromjpeg($file);
				break;
			case "image/gif" :
				$source = imagecreatefromgif($image);
				break;
			default :
				$this->app->addError("Das Logo hat ein ungültiges Format.");
				return false;
		}
        
//        $source = imagecreatefromjpeg($file); 

        // Creating the Canvas 
        $bwimage= imagecreate($width, $height); 

        //Creates the 256 color palette
        for ($c=0;$c<256;$c++) 
        {
            $palette[$c] = imagecolorallocate($bwimage,$c,$c,$c);
        }

        //Creates yiq function
        function yiq($r,$g,$b) 
        {
            return (($r*0.299)+($g*0.587)+($b*0.114));
        }   

        //Reads the origonal colors pixel by pixel 
        for ($y=0;$y<$height;$y++) {
            for ($x=0;$x<$width;$x++) {
                $rgb = imagecolorat($source,$x,$y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                //This is where we actually use yiq to modify our rbg values, and then convert them to our grayscale palette
                $gs = yiq($r,$g,$b);
                imagesetpixel($bwimage,$x,$y,$palette[$gs]);
            }
        } 

        // Outputs a jpg image, but you can change this to png or gif if that is what you are working with
        imagejpeg($bwimage); 
        return $bwimage;
    }

}

?>
