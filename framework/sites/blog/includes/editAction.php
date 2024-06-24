<?php
$user = $this->app->getUser();
// Aktionen:  2: Redakteur, 3: Moderator, 5: I_Admin
$right = ($this->acl->checkRight(2) || $this->acl->checkRight(3) || $this->acl->checkRight(5));
$isAdmin = ($this->acl->checkRight(3) || $this->acl->checkRight(5)) ? true : false;
$writeas = false; // Wird true, wenn Verfasser-Selectbox angezeigt wird.

if (!$right) $this->app->forward("404");

$id = $this->request->args[0];
$this->app->registerJS("res/js/jquery/jquery.form.js");
$this->app->registerJS("res/js/ckeditor/ckeditor.js");
$this->app->registerJS("res/js/ckeditor/adapters/jquery.js");
$this->app->registerCSS("res/css/form.css");
$this->app->registerCSS("res/css/blog.css");
$this->app->registerJS("res/js/admin.js");
$this->app->registerCSS("res/css/jquery.tagedit.css");
$this->app->registerJS("res/js/jquery/jquery.tagedit.js");
$this->app->registerJS("res/js/jquery/jquery.autoGrowInput.js");
$this->app->registerJS("res/js/jquery/jquery.maxlength.js");
$this->app->registerCSS("res/css/jquery-ui/jquery-ui.css");
$this->app->registerJS("res/js/jquery/jquery-ui.js");

$this->app->registerOnLoad("
	$('#tagedit .tag').tagedit({
		maxtags: 10,
		autocompleteOptions: {
			source: function(req, add) {
				$.post(rootdir + 'ajax/blog/getautotags', req, function(data) {
					var suggestions = [];
					$.each(data, function(i, val) {suggestions.push(val)});
					 add(suggestions);
				}, 'json');
			},
		}
	});
	$('#title').maxlength({maxCharacters: 120, statusText:'Zeichen übrig'});
	$('#description').maxlength({maxCharacters: 600, statusText:'Zeichen übrig'});
	$('#text').ckeditor({toolbar: editoroptions});
");

$id = $this->request->args[0];
if ($id == "new") {
	$id = 0;
	$b = new \Datacontainer\Blog();
	$tags = array();

	$this->app->addTitle("Blog-Beitrag erstellen");
	$this->ACTION = "Neuen Blog-Beitrag verfassen";
	$this->COMMENTS_1_CHECKED = "checked='checked'";


} else {
	$this->app->addTitle("Blog-Beitrag bearbeiten");
	$this->ACTION = "Blog-Beitrag bearbeiten";
	$id = intval($id);
	$b = new \Datacontainer\Blog($id);
	$image = $b->id."_".$b->bild_hash;
	if (file_exists(FRAMEWORK_DIR."res/uploads/images/blog/".$image."_k.jpg")) {
		$tplImage = $this->getSubtemplate("IMAGE");
		$tplImage->IMAGE = $image;
		$this->IMAGE = $tplImage->render(true);
	}
	if ($b->user_id != $user->id && !$this->acl->checkRight(3) && !$this->acl->checkRight(5)) $this->app->forward("404");
	$this->COMMENTS_1_CHECKED = ($b->comments > 0) ? "checked='checked'" : "";
	$tags = $this->query("blog_tags")->where("blog_id = ".$id)->order("tag")->asArray("tag")->run();
}

$this->TITLE_VALUE = str_replace("\"", "&quot;", $b->title);
$this->TEXT_VALUE = $b->text;
$this->DESCRIPTION_VALUE = str_replace("\"", "&quot;", $b->description);

$tags = array_keys($tags);
$tags = array_map("trim", $tags);
$tags = array_map("strtolower", $tags);
$tags = array_unique($tags);
if (count($tags) == 0) $tags[] = "";

$tplTag = $this->getSubtemplate("TAG");
foreach($tags AS $tag) {
	$tplTag->VALUE = $tag;
	$this->TAGS .= $tplTag->render();
	$tplTag->resetParser();
}

$form = new \Form($this, "form_blog");
$form->registerElement("title", "any", true, "Bitte geben Sie eine Überschrift an.");
$form->registerElement("text", "any", true, "Bitte geben Sie einen Text an.");
$form->registerElement("description", "any", true, "Bitte geben Sie eine Kurzbeschreibung an.");

if ($this->request->method == "POST") {
	$form->setFormData($this->request->post);
	$success = $form->run();

	if ($success) {
		if ($id == 0) {
			$b->user_id = $user->id;
		}
		$b->title = $this->request->post["title"];
		$b->text = $this->request->post["text"];
		$b->description =  strip_tags($this->request->post["description"]);
		$b->comments = ($this->request->post["comments"] == 1) ? 1 : 0;
		$user_id = $user->id;

		$id = $b->save();
		$tags =  $this->request->post["tags"];
		$this->db->query("DELETE FROM blog_tags WHERE blog_id = ".$id);
		foreach($tags AS $tag) {
			$bt = new \Datacontainer\Blog_tags();
			$bt->blog_id = $id;
			$bt->tag = $tag;
			$bt->save(false);
		}


		require_once(FRAMEWORK_DIR."sites/imageupload/Imageupload.php");
		$imageupload = new \Sites\Imageupload();
		$avatarDir = FRAMEWORK_DIR . "res/uploads/";
		if (!file_exists($avatarDir)) mkdir ($avatarDir);
		$avatarDir .= "images/";
		if (!file_exists($avatarDir)) mkdir ($avatarDir);
		$avatarDir .= "blog/";
		if (!file_exists($avatarDir)) mkdir ($avatarDir);

		$tmpDir = FRAMEWORK_DIR . "res/uploads/tmp/";
		if (!file_exists($tmpDir)) mkdir ($tmpDir);

		$f = $_FILES["blogimage"];
		if ($f['tmp_name']) {
			if (!getimagesize($f['tmp_name'])) {
				$this->app->addError("Das hochgeladene Bild ist keine Bilddatei.");
			} else {
				$tmpname = $tmpDir.$id."_".$f["name"];
				$hash = md5(microtime(true));
				$filename = $id."_".$hash;
				$finalname = $avatarDir.$filename;
				$oldname = $avatarDir.$id."_".$b->bild_hash;
				if (move_uploaded_file($f['tmp_name'], $tmpname))
				{
					$imageupload->resizeImage($f, $tmpname, "400", $finalname."_k.jpg", "both");
					$imageupload->resizeImage($f, $tmpname, "1000", $finalname.".jpg", "width");
					@unlink($oldname."_k.jpg");
					@unlink($oldname.".jpg");
					@unlink ($tmpname);
					//$filename = "res2/uploads/".$id."/images/".$filename;
					$b->bild_hash = $hash;
					$b->save(false);
				}

			}
		}


		$this->app->addSuccess("Der Beitrag wurde gespeichert");
		$this->app->forward("blog/show/".$id."-".\Utils::normalizeUrl($b->title));
	}
}
$this->app->addLocation("blog/edit/".$id, $this->ACTION);

