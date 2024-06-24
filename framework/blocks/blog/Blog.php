<?php
namespace Blocks;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Blog extends \Controller
{
    public function indexAction()
    {

    }

	public function detailsAction(\Datacontainer\Blog $blog) {
        $this->app->registerCSS("res/css/blog.css");
		$b = $blog->getAllValues();
        $blog_link = "/blog/show/".$b["id"]."-".\Utils::normalizeUrl($b["title"]);
        $this->app->addLocation( $blog_link, $b["title"]);
		$tags = $this->query("blog_tags")->where("blog_id = ".$b["id"])->order("tag")->asArray()->run();
		foreach($tags AS $t) {
			if (!isset($b["tags"])) $b["tags"] = array();
			$b["tags"][] = $t["tag"];
		}
		
		$this->BLOG = $this->getBlog($b);
		
	}
	
    public function listAction(Array $blogs)
    {
        $this->app->registerCSS("res/css/blog.css");

		if (count($blogs) > 0) {
			$ids = array_keys($blogs);
			$tags = $this->query("blog_tags")->where("blog_id IN (".implode(",", $ids).")")->order("tag")->asArray()->run();
			foreach($tags AS $t) {
				if (!isset($blogs[$t["blog_id"]]["tags"])) $blogs[$t["blog_id"]]["tags"] = array();
				$blogs[$t["blog_id"]]["tags"][] = $t["tag"];
			}
		}

		foreach ($blogs AS $b) {
			$this->BLOG .= $this->getBlog($b);
        }
        $this->name = $return;
    }
	
	private function getBlog($b) {
		$tplBlog = $this->getSubtemplate("BLOG");
        $tplTag = $this->getSubtemplate("TAG");
        $tplImage = $this->getSubtemplate("IMAGE");
        $tplAdmin = $this->getSubtemplate("ADMINBUTTONS");
		
		$tplBlog->ID = $b["id"];
		$tplBlog->TITLE = $b["title"];

		$tplBlog->TITLE_URL = "-".\Utils::normalizeURL($b["title"]);
		$tmpZeit = new \DateTime($b["created_at"]);
		$zeit = \Utils::getDayname($tmpZeit->format("w")).", ";
		$zeit.= $tmpZeit->format("d. ");
		$zeit.= \Utils::getMonthname($tmpZeit->format("n"))." ";
		$zeit.= $tmpZeit->format("Y");
		$tplBlog->ZEIT = $zeit;
		$tplBlog->TEXT = $b["text"];
		$tplBlog->SHORT = \Utils::shortenString($b["description"], 600);

		$u= new \Datacontainer\User($b["user_id"]);
		$tplBlog->AUTHOR = $u->forename." ".$u->surname;


		if ($b["bild_hash"]) {
			$tplBlog->BLOG_CLASS = "col-sm-8 col-md-9";
			$tplImage->IMAGEURL = "res/uploads/images/blog/".$b["id"]."_".$b["bild_hash"]."_k.jpg";
			$tplImage->TITLE = str_replace('"', "&quot;", $b["title"]);
			$tplImage->ID = $b["id"];
			$tplImage->TITLE_URL = "-".\Utils::normalizeURL($b["title"]);
			$tplBlog->IMAGE = $tplImage->render();
			$tplImage->resetParser();
			if ($writeTitle) {
				$this->app->setFbImage("//nreins.de".str_replace(".thumbs/", "", $info["thumb"]));
			}
		} else {
			$tplBlog->BLOG_CLASS = "col-xs-12";
		}
		$tags = array_map("trim", $b["tags"]);
		$tags = array_map("strtolower", $tags);
		$tags = array_unique($tags);
		if (count($tags) > 10) {
			shuffle($tags);
			$tags = array_slice($tags, 0, 10);
			sort($tags);                    
		}
		foreach($tags AS $tag) {
				$tplTag->TAG = $tag;
				$tplTag->TAG_URL = urlencode($tag);
				$tplBlog->TAGS .= $tplTag->render();
				$tplTag->resetParser();
		}

		if (
				$b["user_id"] == $this->app->getuser()->id
				|| $this->acl->checkRight(MODERATOR)
				|| $this->acl->checkRight(I_ADMIN)
				|| $this->acl->checkRight(S_ADMIN)
		) {
				$tplAdmin->ID = $b["id"];
				$tplAdmin->TITLE = htmlentities($b["title"]);
				$tplBlog->ADMINBUTTONS = $tplAdmin->render();
				$tplBlog->CLICKS = $b["count"]." Klicks";
				$tplAdmin->resetParser();
		}

		$user = $this->app->getUser();
		if ($user->isLoggedIn()) {
			if (isset($watchlist_ids[$b["id"]])) {
				$tplMerken = $this->getSubtemplate("WATCHLIST_DEL");
				$tplMerken->ID = $watchlist_ids[$b["id"]]["id"];
			} else {
				$tplMerken = $this->getSubtemplate("WATCHLIST_ADD");
				$tplMerken->ID = $b["id"];
			}
			$tplBlog->MERKEN = $tplMerken->render();
		}
		
		return $tplBlog->render(true);
	}

    public function getName() {
        return $this->name;
    }
    
    public function tagcloudAction() {
        $tpl = $this->getSubTemplate("TAG");
        $tags = $this->db->getTags(30);
        $min = min($tags);
        $max = max($tags) - $min;
        $size = 0.8;
        $color = 144;
        foreach($tags AS $tag => $count) {
            $teiler = ($count - $min) * .8/$max;
            $tmpsize = $size + $teiler;
            $tmpcolor = dechex(intval($color - ($teiler * 80)));
            $tpl->SIZE = $tmpsize;
            $tpl->COLOR = $tmpcolor;
            $tpl->TAG = $tag;
            $tpl->TAG_URL = urlencode($tag);
            $this->TAGS .= $tpl->render();
            $tpl->resetParser();
        }
    }

    
    public function getImageInfo($i) {
        $maxsize = 100;
        $thumb = $image = $i;
        if (stristr($i, "http://") || stristr($i, "https://")) {}
        else if (stristr($i, "/res2/")) {
                $image = preg_replace("|/res2/|", FRAMEWORK_DIR . "/res/", $i, 1);
                if (!stristr($i, "/.thumbs/")) {
                    $image2 = preg_replace("|/images/|", "/.thumbs/images/", $image, 1);
                    if (file_exists($image2)) {
                        $thumb = preg_replace("|/images/|", "/.thumbs/images/", $i, 1);
                    }
                }
        }

        $size = getimagesize($image);
        switch($size["mime"]) {
                case "image/png" :
                case "image/jpeg" :
                case "image/jpg" :
                case "image/gif" :
                        break;
                default :
                        continue;
        }
        if ($size[0] > $size[1]) {
                $width = $maxsize;
                $height = floor(($size[1] / $size[0]) * $maxsize);
                $padding_left = 0;
                $padding_top = floor(($width-$height)/2);
        } else {
                $height = $maxsize;
                $width = floor(($size[0] / $size[1]) * $maxsize);
                $padding_left = floor(($height-$width)/2);
                $padding_top = 0;
        }

        if (!file_exists())
        return array(
                "image" => $image,
                "thumb" => $thumb,
                "width" => $width,
                "height" => $height,
                "padding_left" => $padding_left,
                "padding_top" => $padding_top
        );

    }
    
}

?>
