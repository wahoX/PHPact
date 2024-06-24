<?php
namespace Sites;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Blog extends \Controller
{
	private $show = 10;
	
    public function __construct($template = "", $tag = "") {
        parent::__construct($template, $tag);
        $this->app->forward("");
        $this->app->addLocation("blog", "News");

        $this->app->registerJs("res/js/parallax.min.js");
        $this->app->registerOnload("
            $('#header-image').parallax({
                imageSrc: '/res/images/reichenbach.jpg',
                naturalWidth: 1920,
                naturalHeight: 1080,
                bleed: 0
            });
        ");

    }

    public function indexAction(){
		$right = ($this->acl->checkRight(2) || $this->acl->checkRight(3) || $this->acl->checkRight(5));
		if ($right) {
			$this->BLOG_NEW = $this->getSubTemplate("BLOG_NEW")->render();
		}

        $page = intval($this->request->args[0]);
        if (!$page) $page++;
        $show = $this->show;
        $start = ($page-1) * $show;

		$q = $this->query("blog")->what("id")->asArray();
		$blogs = $q->run();
        $count = count($blogs);
        $this->pagination("/index/", $page, $count, $start, $show);

        $q = $this->query("blog")->order("ID desc")->limit($show)->start($start)->asArray("id");
		$blogs = $q->run();
		
        $block = new \Block("Blog", "list", $blogs);
        $this->BLOG .= $block->render();
		$this->TITLE = "News / Blog";
		$this->app->addTitle("Blog");
        $this->app->addDescription("Erfahren Sie Neuigkeiten über uns");
        $this->app->addKeywords("Blogs, Informationen, Neuigkeiten, News, Tipps, Veranstaltungen, Events");
    }

    private function pagination($url, $page, $count, $start, $show) {
        $pages = ceil($count/$show);
        if ($pages < 2) return;

        $tplPagination = $this->getSubTemplate("PAGINATION");
        $tplPage = $this->getSubTemplate("PAGE");


        $tmp = array(1);
        if ($pages >= 2) $tmp[] = 2;
    #        if ($pages >= 3) $tmp[] = 3;

        $tmp[] = $pages;
        if ($pages-1 >= 1) $tmp[] = $pages-1;
    #       if ($pages-2 >= 1) $tmp[] = $pages-2;

        $tmp[] = $page;
        if ($page-1 >= 1) $tmp[] = $page-1;
        if ($page+1 <= $pages) $tmp[] = $page+1;

        $tmp = array_unique($tmp);
        sort($tmp);


        if ($page > 1) {
            if ($url == "/index/" && $page==2) $tplPage->URL = "";
            else $tplPage->URL = $url.($page-1);
            $tplPage->PAGE = "&laquo;";
            $tplPagination->BACK = $tplPage->render();
            $tplPage->resetParser();
        }

        if ($page < $pages) {
            $tplPage->URL = $url.($page+1);
            $tplPage->PAGE = "&raquo;";
            $tplPagination->FORWARD = $tplPage->render();
            $tplPage->resetParser();
        }

        $last = 0;
        foreach ($tmp AS $i) {
            if ($last < ($i-1)) $tplPagination->PAGES .= "<li><a>..</a></li>";
            $last = $i;
            if ($url == "/index/" && $i==1) $tplPage->URL = "";
            else $tplPage->URL = $url.$i;
            $tplPage->PAGE = $i;
            if ($page == $i) {
                $tplPage->ACTIVE = " class='active'";
                if ($i > 1) $this->app->addLocation($this->request->url, "Seite ".$i);
            }
            $tplPagination->PAGES .= $tplPage->render();
            $tplPage->resetParser();
        }

        $this->PAGINATION_BOTTOM = $tplPagination->render();
    }

    public function showAction() {
        $id = intval($this->request->args[0]);
        if ($id < 1) $this->app->forward("blog");
		$b = new \Datacontainer\Blog($id);
		if (!$b->id) $this->app->forward("oops");
		
		$this->BLOG = (new \Block("blog", "details", $b))->render();
		
		$b->count++;
		$b->save();

		$name = \Utils::shortenString($this->show($blogs, true, true), 60);
        $this->app->addLocation($this->request->url, $name);
    }

    public function tagAction() {
		$tag = $this->request->args[0];
        $this->TITLE = "Blogs zum Thema &quot;" . ucfirst($tag)."&quot;";
		
        $page = intval($this->request->args[1]);
        if (!$page) $page++;
        $show = $this->show;
        $start = ($page-1) * $show;

		$blogs = $this->query("blog_tags")->what("blog_id")->where("tag LIKE '".\Utils::escape($tag)."'")->asArray("blog_id")->run();
        $count = count($blogs);
		$ids = array_keys($blogs);
        $this->pagination("/tag/".$tag."/", $page, $count, $start, $show);
		if (count($ids) > 0) {
			$blogs = $this->query("blog")->where("id IN (".implode(",", $ids).")")->order("ID desc")->limit($show)->start($start)->asArray("id")->run();
		} else {
			$blogs = array();
		}
		
        $block = new \Block("Blog", "list", $blogs);
        $this->BLOG .= $block->render();

		
        $this->app->addTitle("Blogs zum Thema ".ucfirst($this->request->args[0]));
        $this->app->addDescription("Blogs zum Thema ".ucfirst($this->request->args[0])." - Im Blog von NrEins.de haben Sie als Unternehmer auch die Möglichkeit, Beiträge zu verfassen.");
        $this->app->addKeywords("Blog, NrEins.de, Unternehmer, ".$this->request->args[0]);
        $this->app->addLocation($this->request->url, "Blogs zum Thema ".ucfirst($this->request->args[0]));

    }

    public function editAction() {
        require_once(__DIR__."/includes/".__FUNCTION__.".php");
    }
    
    private function getImageInfo($i) {
        $block = new \Block("Blog");
        return $block->getImageInfo($i);
    }

    public function deleteAction() {
        $id = intval($this->request->args[0]);
		if ($b->user_id != $this->app->getUser()->id && !$this->acl->checkRight(3) && !$this->acl->checkRight(5)) {
			$this->app->forward("oops");
		}
		$b = new \Datacontainer\Blog($id);
		$b->delete();
        $this->app->addSuccess("Der Beitrag wurde gelöscht.");
        $this->app->forward("blog");
    }
	
	public function enableAction() {
		if (!$this->acl->checkRight(MODERATOR) && !$this->acl->checkRicht(I_ADMIN)) {
			$this->app->forward("oops");
		}
		$id = intval($this->request->args[0]);
		$b = new \Datacontainer\Blog($id);
		$b->enabled = 1;
		$b->save();
		$this->app->addSuccess("Der Beitrag wurde freigeschaltet.");
		$this->app->forward($this->request->referrer);
	}
	
	public function disableAction() {
		if (!$this->acl->checkRight(MODERATOR) && !$this->acl->checkRicht(I_ADMIN)) {
			$this->app->forward("oops");
		}
		$id = intval($this->request->args[0]);
		$b = new \Datacontainer\Blog($id);
		$b->enabled = 0;
		$b->save();
		$this->app->addSuccess("Der Beitrag wurde gesperrt.");
		$this->app->forward($this->request->referrer);
	}

    public function getautotagsAction() {
        $value = $this->request->post['term'];
        $return = array();

        $result = $this->db->getAutoTags($value, 15);
        foreach ($result as $wert) {
            $tags = new \stdClass();
            $tags->id = '';
            $tags->label = $wert;
            $tags->value = $wert;
            $return[] = $tags;
        }
        $this->app->addAjaxContent(json_encode($return));
    }
    
    public function searchAction() {}

}
