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

class Form extends \Controller
{
	
	private $js;
	
	public function indexAction()
	{		
	}
	
	public function contactAction()
	{
		$user = $this->app->getUser();
		$guest = $this->acl->checkRight(GUEST);	
		
		$form = new \Form($this, "contact_form");
		$form->registerElement("title", "select", true, ""); 											//Anrede	
		$form->registerElement("vorname", "any", true, "Bitte geben Sie Ihren Vornamen an."); 	//Benutzername
		$form->registerElement("name", "any", true, "Bitte geben Sie Ihren Namen an."); 	//Benutzername
		$form->registerElement("city", "any", false, "Erlaubte Zeichen: a-z, A-Z, 0-9, -, _"); //Ort
		$form->registerElement("email", "email", true, "Bitte geben Sie eine gültige E-Mail-Adresse an!"); 	//Email-Adresse
		$form->registerElement("phone", "phone", false, "Bitte geben Sie eine gültige Telefonnummer an!");	//Telefonnummer		
		$form->registerElement("xhisvg", "any", true, "Bitte geben Sie eine Nachricht ein!");				//Nachricht

		if ($this->request->post["contact_submit"] != "")
		{
			
			if ($this->request->post["subject"] != "" || $this->request->post["message"] != "") {
				$this->app->forward("");
			}
			
			$form->setFormData($this->request->post);

			//Anrede auswerten und gewählte Auswahlbei Neuaufruf checken
			if($this->request->post["title"] == "Herr") { $this->TITLE_SELECTED_HERR = "checked"; }	
			if($this->request->post["title"] == "Frau") { $this->TITLE_SELECTED_FRAU = "checked"; }	
			if($this->request->post["title"] == "Firma") { $this->TITLE_SELECTED_FIRMA = "checked"; }	
												
			$success = $form->run();				
			if (!$success)
				$this->app->addError("* Bitte füllen Sie das Formular mit korrekten Daten aus.");

			// EMail oder Telefon angegeben?
			if(!$this->request->post["email"] && !$this->request->post["phone"]) {
				$success = false;	
				$this->app->addError("* Bitte geben Sie E-Mail oder Telefonnummer an, damit wir Sie kontaktieren können.");
			}
			
			if($success) {
				//$this->app->addSuccess("Ihre Nachricht wurde übermittelt.");

				$topic = $this->request->post["topic"];
				$title = $this->request->post["title"];								
				$vorname = htmlentities(\Utils::str_encode($this->request->post["vorname"]));
				$name = htmlentities(\Utils::str_encode($this->request->post["name"]));
				$city = htmlentities(\Utils::str_encode($this->request->post["city"]));					
				$email = $this->request->post["email"];	
				$phone = htmlentities(\Utils::str_encode($this->request->post["phone"]));		
				$message = htmlentities(\Utils::str_encode($this->request->post["xhisvg"]));
				$ip = $this->request->ip;
if ($topic != "") $topic2 = "<br /><b>Thema:</b> ".$topic."<br />";

				$mailtext = "
Sie haben eine neue Anfrage &uuml;ber das Kontaktformular erhalten:<br />
".$topic2."
<br />
<b>Die Anfrage wurde gestellt &uuml;ber:</b> ".$this->request->referrer."<br />
<br />
<b>Anrede:</b> ".$title."<br />
<b>Name:</b> ".$vorname." ".$name."<br />
<b>E-Mail:</b> ".$email."<br />
<b>Telefon:</b> ".$phone."<br />
<b>Ort:</b> ".$city."<br />
<b>Nachricht:</b><br />
".nl2br(strip_tags($message));

				$mailtext = \Utils::str_encode($mailtext, "UTF-8");

#				$recipient = $this->db->getUserById($this->app->getOwner());
				$recipient["email"] = "stefan@jutzi.net";
				$mail = $this->app->getModule("Email");

				#if (!$topic) $topic = "Kontakt-Formular";
				$tmp = $mail->send($recipient["email"], "Kontaktformular - Anfrage", $mailtext, true, $sender="Kontaktformular <kontaktformular@jutzi.net>");
				$this->app->forward($this->request->root."form/success");
			}
		}
	}
	
	public function successAction()
	{
		$this->COMPANY = COMPANY;
	}
	
}


?>
