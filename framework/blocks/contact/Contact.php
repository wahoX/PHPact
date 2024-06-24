<?php
namespace Blocks;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Contact extends \Controller
{
		
	public function indexAction()
	{
		$form = new \Form($this, "contact_form");
		$form->registerElement("vorname", "any", true, "Bitte geben Sie Ihren Vornamen an."); 	//Benutzername
		$form->registerElement("name", "any", true, "Bitte geben Sie Ihren Namen an."); 	//Benutzername
		$form->registerElement("city", "any", false, "Erlaubte Zeichen: a-z, A-Z, 0-9, -, _"); //Ort
		$form->registerElement("email", "email", true, "Bitte geben Sie eine gültige E-Mail-Adresse an!"); 	//Email-Adresse
		$form->registerElement("phone", "phone", false, "Bitte geben Sie eine gültige Telefonnummer an!");	//Telefonnummer		
		$form->registerElement("xhisvg", "any", true, "Bitte geben Sie eine Nachricht ein!");				//Nachricht


        if ($this->request->method=="POST") {
            if ($this->request->post["subject"] != "" || $this->request->post["message"] != "") $this->app->forward("oops");
            $success = $form->run();
            if ($success) {

				$vorname = htmlentities(\Utils::str_encode($this->request->post["vorname"]));
				$name = htmlentities(\Utils::str_encode($this->request->post["name"]));
				$city = htmlentities(\Utils::str_encode($this->request->post["city"]));					
				$email = $this->request->post["email"];
				$phone = htmlentities(\Utils::str_encode($this->request->post["phone"]));		
				$message = htmlentities(\Utils::str_encode($this->request->post["xhisvg"]));
				$ip = $this->request->ip;

                $mailtext = "
<h3>Kontakt-Anfrage über rotwolf-siegen.de</h3>
<b>Name:</b> ".$vorname." ".$name."<br>
<b>Ort:</b> ".$city."<br>
<b>E-Mail:</b> ".$email."<br>
<b>Telefon:</b> ".$phone."<br>
<b>Nachricht:</b><br>
".nl2br($message);

                $mailtext = \Utils::str_encode($mailtext, "UTF-8");
				$recipient = $this->db->getUserById($this->app->getOwner());
                $recipient["email"] = "stjutzi@gmail.com";
                #$recipient["email"] = "info@rotwolf-siegen.de";
				$mail = $this->app->getModule("Email");
				$mail->setReplyTo($email);

				#if (!$topic) $topic = "Kontakt-Formular";
				$tmp = $mail->send($recipient["email"], "Kontaktanfrage auf rotwolf-siegen.de", $mailtext, true, $sender="Kontaktformular <kontaktformular@dd-sign.de>");
				print_r($tmp);die;
				$this->app->forward("content/danke");


            }
        }
	}	
}
