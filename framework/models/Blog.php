<?php

namespace Models;

class Blog extends \DB
{

    private static $instance = NULL;

    public static function getInstance() {
            if (self::$instance == NULL) {
                    self::$instance = new Blog();
            }
            return self::$instance;
    }

    public function getBlog($id=0, $user_id=0, $site_id=0, $tag="", $branchen_id=0, $start=0, $count=1000, $getCount=false) {
        $filter = "1";
        $start = intval($start);
        $count = intval ($count);
        
		
        $user_id = intval($user_id);
        if ($user_id > 0) $filter .= " AND ".DB_SUFFIX."user.id = $user_id AND branchen_id = 0";

        $site_id = intval($site_id);
        if ($site_id > 0) $filter .= " AND site_id = '$site_id'";

        $branchen_id = intval($branchen_id);
        if ($branchen_id > 0) $filter .= " AND branchen_id = $branchen_id";
        
        $id = intval($id);
        if ($id > 0) {
			$filter .= " AND blog.id = '$id'";
		}

        $return = array();

        $tag = \Utils::escape($tag);
        if ($tag != "") {
            $tmp = array();
            $result = $this->query("SELECT blog_id FROM blog_tags WHERE tag LIKE '$tag'");
            while ($r = $this->fetch_assoc($result)) {
                $tmp[] = $r["blog_id"];
            }
            if (count($tmp) > 0) $filter .= " AND blog.id IN (".implode(",", $tmp).")";
        }

        if ($getCount) {
            $sql = "
                SELECT blog.id
                FROM blog
                LEFT JOIN ".DB_SUFFIX."user ON blog.user_id = ".DB_SUFFIX."user.id
                LEFT JOIN branchen ON blog.branchen_id = branchen.id
                WHERE $filter
            ";
            $result = $this->query($sql);
            return $this->num_rows ($result);
        }
        $sql = "
			SELECT blog.*,
			".DB_SUFFIX."user.id AS user_id,
			".DB_SUFFIX."user.username,
			".DB_SUFFIX."user.forename,
			".DB_SUFFIX."user.surname,
			branchen.id AS firma_id,
			branchen.firma
			FROM blog
			LEFT JOIN ".DB_SUFFIX."user ON blog.user_id = ".DB_SUFFIX."user.id
			LEFT JOIN branchen ON blog.branchen_id = branchen.id
			WHERE $filter
			ORDER BY id DESC
                        LIMIT $start, $count
		";
		
		$result = $this->query($sql);
        
        while ($tmp = $this->fetch_assoc($result)) $return[$tmp["id"]] = $tmp;

        $keys = array_keys($return);
        if (count($keys) > 0) {
            $result = $this->query("SELECT * FROM blog_tags WHERE blog_id IN (".implode(",", $keys).") ORDER BY blog_id, tag");
            while ($tmp = $this->fetch_assoc($result)) {
                if (!is_array($return[$tmp["blog_id"]]["tags"])) $return[$tmp["blog_id"]]["tags"] = array();
                $return[$tmp["blog_id"]]["tags"][] = $tmp["tag"];
            }
        }

        return $return;
    }
	
	public function logBlog($id) {
		$id = intval($id);
		$this->query("UPDATE blog SET count=count+1 WHERE id=".$id);

		$user_id = intval(\Application::getInstance()->getUser()->id);
		if ($user_id < 2) return;
		$ip = $_SERVER["REMOTE_ADDR"];
		$this->query("
			INSERT INTO blog_access (user_id, blog_id, ip) VALUES ($user_id, $id, '$ip')
			ON DUPLICATE KEY UPDATE time=NOW()
		");
	}

    public function getWatchlistCount() {
        $user_id = \Application::getInstance()->getUser()->id;
        $return = array("product" => 0, "blog" => 0, "company" => 0, "user" => 0, "url" => 0);
        $result = $this->query("SELECT type, count(type) AS c FROM watchlist WHERE user_id = '$user_id' group by type ORDER BY FIND_IN_SET(type, 'product,blog,company,user,url')");
        while ($tmp = $this->fetch_assoc($result)) $return[$tmp["type"]] = $tmp["c"];
        return $return;
    }

    /**
     * Diese Funktion erstellt einen Eintrag in die Merkliste
     *
     * @param Integer $user_id ID des Users
     * @param Integer $site_id ID der Seite, wenn es auf eigener Homepage erstellt wurde
     * @param String $title Name des Eintrages
     * @param String $text Blogbeitrag
     * @param String $comments Sind Kommentare erlaubt?
     * @param Array $tags Stichpunkte
     *
     **/			
    public function newItem($user_id, $branchen_id, $site_id, $title, $text, $description, $titleimage, $comments, $tags, $zanoxurl, $gueltigbis, $offerer_id) {
        $type = \Utils::escape($type);
    	$user_id = intval($user_id);
        $branchen_id = intval($branchen_id);
        $site_id = intval($site_id);
        $title = \Utils::escape(strip_tags($title));
        $text = \Utils::escape($text);
        $description = \Utils::escape(strip_tags($description));
        $titleimage = \Utils::escape(strip_tags($titleimage));
        $comments = intval($comments);
        $zanoxurl = \Utils::escape($zanoxurl);
        $offerer_id = intval($offerer_id);
    	$this->query("INSERT INTO blog (user_id, branchen_id, site_id, title, text, description, titleimage,  comments, created_by, zanoxurl, gueltigbis, offerer_id) VALUES ('$user_id', '$branchen_id', '$site_id', '$title', '$text', '$description', '$titleimage', '$comments', '$user_id', '$zanoxurl', '$gueltigbis', '$offerer_id')");
        $id = $this->getLastInsertID();
        foreach($tags AS $t) {
            $t = strtolower(\Utils::escape($t));
            $this->query("INSERT INTO blog_tags (blog_id, tag) VALUES ($id, '$t')");
        }
        return $id;
    }

    /**
     * Diese Funktion ändert einen Eintrag in der Merkliste
     *
     * @param Integer $id ID des Items in watchlist
     * @param String $title Name des Eintrages
     * @param String $text Blogbeitrag (Text)
     * @param String $comments Sind Kommentare erlaubt?
     * @param Array $tags Stichpunkte
     *
     **/			
    public function editItem($id, $title, $text, $description, $titleimage, $comments, $tags, $zanoxurl, $gueltigbis, $offerer_id) {
		$id = intval($id);
        $branchen_id = intval($branchen_id);
        $title = \Utils::escape(strip_tags($title));
        $text = \Utils::escape($text);
        $description = \Utils::escape(strip_tags($description));
        $titleimage = \Utils::escape(strip_tags($titleimage));
        $comments = intval($comments);
        $zanoxurl =  \Utils::escape(strip_tags($zanoxurl));
        $offerer_id = intval($offerer_id);
        
        if ($this->checkBlogRight($id)) {
            $zanox = "";
            if (\ACL::getInstance()->checkRight(S_ADMIN)) {
                $zanox = ", `zanoxurl` = '$zanoxurl', `gueltigbis` = '$gueltigbis', `offerer_id` = '$offerer_id'"; 
            }
            $this->query("UPDATE blog SET `title`='$title', `text`='$text', `description`='$description', `titleimage`='$titleimage', `comments`='$comments', modified_by='$user_id', modified_at=NOW() $zanox WHERE id = ".$id);
            $this->query("DELETE FROM blog_tags WHERE blog_id = ".$id);
            foreach($tags AS $t) {
				$t = \Utils::escape($t);
				$this->query("INSERT INTO blog_tags (blog_id, tag) VALUES ($id, '$t')");
			}
		} else \Application::getInstance()->forward("404");
        
    }
    
    public function checkBlogRight($blog_id) {
        $ok = false;
        $user = \Application::getInstance()->getUser();
        $tmp = $this->query("SELECT user_id, branchen_id FROM blog WHERE id = '".$blog_id."'");
        if ($b = $this->fetch_assoc($tmp)) {
            if ($b["user_id"] > 0 && $user->id == $b["user_id"]) $ok = true;
            $firmen = $this->getCompanies($user->id, $user->vp_nummer);
            if ($b["branchen_id"] > 0 && isset($firmen[$b["branchen_id"]])) $ok = true;
            if (\ACL::getInstance()->checkRight(MODERATOR)) $ok = true;
            if (\ACL::getInstance()->checkRight(I_ADMIN)) $ok = true;
            if (\ACL::getInstance()->checkRight(S_ADMIN)) $ok = true;
		}
		return $ok;
	}

    /**
     * Diese Funktion löscht einen Eintrag
     *
     * @param Integer $id ID des Eintrages
     *
     **/			
    public function deleteItem($id) {
        $id = intval($id);
        if ($this->checkBlogRight($id)) {
            $this->query("DELETE FROM blog WHERE id = ".$id);
            $this->query("DELETE FROM blog_tags WHERE blog_id = ".$id);
		} else \Application::getInstance()->forward("404");

    }
    
    public function getAutoTags($term, $limit=15) {
        $limit = intval($limit);
        $SQLString = "SELECT `tag` FROM blog_tags WHERE `tag` LIKE '" . $term . "%' GROUP BY `tag` ORDER BY COUNT(tag) DESC LIMIT $limit";
        $result = $this->query($SQLString);
        while ($zeile = $this->fetch_assoc($result)) {     
            $return[]= $zeile["tag"];
        }
        sort($return);
        return $return;
    }
    
    public function getTags($num) {
        $num = intval($num);
        $sql = "SELECT tag, count(tag) AS c FROM `blog_tags` GROUP BY tag ORDER BY count(tag) DESC LIMIT $num";
        $result = $this->query($sql);
        $return = array();
        while ($tmp = $this->fetch_assoc($result)) {
            $return[$tmp["tag"]] = $tmp["c"];
        }

        $keys = array_keys($return);
        shuffle($keys);
        $random = array();
        foreach ($keys as $key)
            $random[$key] = $return[$key];
        
        return $random;
    }
    
}
