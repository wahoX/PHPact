<?php

if (!$this->acl->checkRight(S_ADMIN) && !$this->acl->checkRight(I_ADMIN)) {
    $this->app->forward("oops");
}

if ($this->request->args[0] == "edit") {
    $tplForm = $this->getSubtemplate("FORM");
    $tplAscii = $this->getSubtemplate("ASCII");
    $seo = new \Datacontainer\Seo(intval($this->request->args[1]));
    $tplForm->ID = $seo->id;
    $tplForm->URL = $seo->url;
    $tplForm->SEO_TITLE = htmlentities($seo->title);
    $tplForm->SEO_DESCRIPTION = htmlentities($seo->description);
    $tplForm->SEO_KEYWORDS = htmlentities($seo->keywords);
    $ascii = array("#9658", "#10004", "#10008", "#9835", "#9742", "#9829", "#10067", "#10071", "#10172", "raquo", "rArr", "rarr", );
    foreach($ascii AS $a) {
        $tplAscii->NUMBER = $a;
        $tplForm->ASCII .= $tplAscii->render(true);
    }
    $this->app->addAjaxContent($tplForm->render());
} else if ($this->request->method == "POST") {
    $id = intval($this->request->post["id"]);
    $s = new \Datacontainer\Seo($id);
    $s->title = $this->request->post["title"];
    $s->description = $this->request->post["description"];
    $s->keywords = $this->request->post["keywords"];
    $s->optimiert = 1;
    $s->save();
    $this->app->addSuccess("Die SEO-Daten von &quot;/".$s->url."&quot; wurden bearbitet.");
    $show = (isset($this->request->get["show"])) ? "?show=".$this->request->get["show"] : "";
    $this->app->forward($this->request->url.$show);
} else {
    $this->app->registerJS("res/js/jquery/jquery.maxlength.js");
    $this->app->registerCss("
        .status {
            bottom: 1px;
            right: 1px;
            border-radius:4px;
            padding: 2px 5px;
            font-size:12px;
            display:inline-block;
            border:1px solid #800;
            background:#fee;
            color:#800;
            position: absolute;
            z-index:99;
        }
    ", 1);
    $tplRow = $this->getSubtemplate("ROW");
    if (isset($this->request->get["show"])) {
        $filter = "1";
        $this->SHOW_FILTER = "";
        $this->SHOW = "nicht bearbeitete";
    } else {
        $filter = "optimiert=0 OR title='' OR description='' OR keywords=''";
        $this->SHOW_FILTER = "?show=all";
        $this->SHOW = "alle";
    }
    $seo = $this->query("seo")->where($filter)->order("url")->asArray()->run();

    //while ($s = $this->db->fetch_assoc($seo)) {
    foreach($seo AS $s) {
        $tplRow->SEO = $s;
        $tplRow->TITLE_LENGTH = strlen($s["title"]);
        $tplRow->DESCRIPTION_LENGTH = strlen($s["description"]);
        $tplRow->KEYWORDS_LENGTH = strlen($s["keywords"]);
        if (strlen($s["title"]) == 0 || strlen($s["title"]) > 60 || strlen($s["description"]) == 0 || strlen($s["description"]) > 160 || strlen($s["keywords"]) > 50) {
            $tplRow->CSS_CLASS = "alert-danger";
        } else if (strlen($s["title"]) < 40 || strlen($s["description"]) < 90) {
            $tplRow->CSS_CLASS = "alert-warning";
        }
        $this->ROWS .= $tplRow->render(true);
    }
}
