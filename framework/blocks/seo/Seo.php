<?php
namespace Blocks;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Seo extends \Controller
{
	public function indexAction()
	{
		if (!$this->app->adminmode() || $this->request->site != "cms") {
			$this->setTemplate("");
			return;
		}

		$this->app->registerJs("res/js/jquery/jquery.form.js");
		$this->app->registerOnLoad("
			$('#toggleseo').click(function(){ $('#seo').slideToggle(); return false; });
			$('#seoform').ajaxForm({
			    success: function(data) {
				data = eval(data);
				if (data[1] == false) {
				    $('#seo_url_wrapper').addClass('formerror');
				    $('#seo_url_errormessage').slideDown();
				} else {
				    $('#seo_url_wrapper').removeClass('formerror');
				    $('#seo_url_errormessage').slideUp();
				    $('#seo_success').slideDown().delay(1500).slideUp();
				}
			    }
			});
		");

		$cms = $this->app->getModule("cms");
		$item = $cms->getItem();
		$this->ID = $item["id"];
		$this->URL =$item["url"];
		$this->TITLE =$item["title"];
		$this->DESCRIPTION =$item["description"];
		$this->KEYWORDS =$item["keywords"];


		$form = new \Form($this, "seoform");
		$form->registerElement("seo_url", "alphanumeric", true); 	// Email-Adresse
	}
}

?>
