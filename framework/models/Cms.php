<?php

namespace Models;

class Cms extends \DB
{
    private static $instance = NULL;

    public static function getInstance() {
            if (self::$instance == NULL) {
                    self::$instance = new Cms();
            }
            return self::$instance;
    }


	public function deleteCmsItem($id)
	{
		$id = intval($id);
		$site = \Application::getInstance()->getSite();
		$user = \Application::getInstance()->getUser();
		$item = $this->getCmsItem($id);
//		$request = \Request::getInstance();
//		if ($item["type"] == "download") {
//			$dir = $_SERVER["DOCUMENT_ROOT"].$request->root."../res/uploads/files/";
//			@unlink($dir.$item["download_link"]);
//		}
//		if ($item["type"] == "picture") {
//			$dir = $_SERVER["DOCUMENT_ROOT"].$request->root."../res/uploads/images/";
//			@unlink($dir.$id."_k.jpg");
//			@unlink($dir.$id.".jpg");
//		}
		$this->query("UPDATE content SET `deleted`= 1, modified_by = '".$user->id."', modified_at=NOW() WHERE id='$id' AND site_id = '$site'");
	}

	public function updateCmsItem($id, $title, $text, $menu_id = "", $type="text", $preview_image="", $preview_anchor="", $preview_link="", $download_link="", $download_type="", $download_size="", $download_name="", $valid_from="", $valid_until="")
	{
		$site = \Application::getInstance()->getSite();
		$user = \Application::getInstance()->getUser();

		$title = \Utils::escape($title);
		$text = \Utils::escape($text);
		$type = \Utils::escape($type);
		$preview_image = \Utils::escape($preview_image);
		$preview_link = intval($preview_link);
		$preview_anchor = intval($preview_anchor);
		$download_link = \Utils::escape($download_link);
		$download_type = \Utils::escape($download_type);
		$download_size = intval($download_size);
		$download_name = \Utils::escape($download_name);

		if ($valid_from) {
			$valid_from = new \DateTime($valid_from." 00:00:00");
			$valid_from = $valid_from->format("Y-m-d H:i:s");
		} else $valid_from = "0000-00-00 00:00:00";

		if ($valid_until) {
			$valid_until = new \DateTime($valid_until." 00:00:00");
			$valid_until = $valid_until->format("Y-m-d H:i:s");
		} else $valid_until = "0000-00-00 00:00:00";

		switch ($_SESSION["language"]) {
			case "en" : $field_title = "title_en"; $field_content = "content_en"; break;
			case "cz" : $field_title = "title_cz"; $field_content = "content_cz"; break;
			default : $field_title = "title"; $field_content = "content"; break;
		}

		if ($preview_image) {
			$set.= ", `preview_image`";
			$values.= ", '$preview_image'";
			$insert .= ", `preview_image` = '$preview_image'";
		}
		if ($preview_anchor) {
			$set.= ", `preview_anchor`";
			$values.= ", '$preview_anchor'";
			$insert .= ", `preview_anchor` = '$preview_anchor'";
		}
		if ($preview_link) {
			$set.= ", `preview_link`";
			$values.= ", '$preview_link'";
			$insert .= ", `preview_link` = '$preview_link'";
		}
		if ($download_link) {
			$set.= ", `download_link`";
			$values.= ", '$download_link'";
			$insert .= ", `download_link` = '$download_link'";
		}
		if ($download_type) {
			$set.= ", `download_type`";
			$values.= ", '$download_type'";
			$insert .= ", `download_type` = '$download_type'";
		}
		if ($download_size) {
			$set.= ", `download_size`";
			$values.= ", $download_size";
			$insert .= ", `download_size` = '$download_size'";
		}
		if ($download_name) {
			$set.= ", `download_name`";
			$values.= ", '$download_name'";
			$insert .= ", `download_name` = '$download_name'";
		}
		if (!stristr($id, "new_")) {
			$id = intval($id);
			$sql = "
				UPDATE content SET 
					`$field_title` = '$title',
					`$field_content` = '$text',
					`modified_by` = '".$user->id."',
                    `modified_at` = NOW(),
					`valid_from` = '".$valid_from."',
					`valid_until` = '".$valid_until."'
					$insert
				WHERE id = '".$id."' AND site_id = ".$site;

			$result = $this->query($sql);
			return $id;
		}
		$menu_id = intval($menu_id);
		$pos = $this->query("SELECT pos FROM content WHERE menu_id = '$menu_id' ORDER BY pos DESC LIMIT 1");
		if ($pos = $this->fetch_array($pos)) $pos = ++$pos[0];
		else $pos = 1;

		$query = "
			INSERT INTO content
				(`type`, `title`, `content`, `site_id`, `menu_id`, `pos`, `modified_by`, `modified_at`, `created_by`, `created_at`, `valid_from`, `valid_until` $set)
			VALUES
				('$type', '$title', '$text', '$site', '$menu_id', '$pos', '".$user->id."', NOW(), '".$user->id."', NOW(), '".$valid_from."', '".$valid_until."' $values)";
		$result = $this->query($query);

		return $this->getLastInsertID();
	}

	public function getDownload($filename) {
		$filename = \Utils::escape($filename);
		$sql = "SELECT download_name, download_size, download_type FROM content WHERE download_link LIKE '$filename'";
		$result = $this->query($sql);
		return $this->fetch_assoc($result);
	}

	public function saveCmsOrder($menu_id, $order) {
		$pos = 0;
		$site = \Application::getInstance()->getSite();
		foreach ($order AS $cid) {
			$pos++;
			$this->query("UPDATE content SET pos = $pos WHERE id = $cid AND site_id = $site");
		}
	}



}
