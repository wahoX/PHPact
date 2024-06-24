<?php
namespace Module;
/**
 * Das Modul Email versendet E-Mails an User.
 */
class Email extends \Module
{
	private $sender = "";
	private $replyto = "";
	private $mail;
    private $initialized = false;
    private $images = array();
	
	public function checkRight() {
		return true;
	}

	public function getUrl() {
		return false;
	}
	
	private function init()
	{
        if ($this->initialized) return;
        $this->initialized = true;

        $logo_url = $this->app->getSettings("logo");
        if (!$logo_url) {
            $logo_url = __DIR__ . "/logo_small.png";
        }
        else {
            $logo_url = FRAMEWORK_DIR . str_replace("res2/", "res/", $logo_url);
        }
		$this->mail = new \Email();
		$this->mail->setSender($this->sender);
		$this->mail->setTemplate(__DIR__."/tpl.html");
        #$this->mail->addImage($logo_url, "LOGO");
		#$this->mail->addImage(__DIR__."/logo.png", "LOGO");
	}

    public function addAttachment($file) {
        $this->init();  
        $this->mail->addAttachment($file);
    }

    
    public function addImage($image_link, $placeholder) {
		$this->images[$placeholder] = $image_link;
	}
	
	public function setReplyTo($email) {
		$this->replyto = $email;
	}

	/**
	 * Diese Funktion verschickt eine E-Mail.
	 *
	 * @param String $email E-Mail Adresse des Empfängers
	 * @param String $subject Betreff der E-Mail
	 * @param String $message Der Text der E-Mail (HTML)
	 * @param Boolean $html true wenn HTML, false wenn nur Text
	 *
	 * @return mixed true, wenn erfolgreich. Object, wenn nicht erfolgreich.
	 **/
	public function send($email, $subject, $message, $html=true, $sender="Stefan Jutzi <stefan.jutzi@wahox.de>")
	{
		$this->init();
		$this->mail->setSender($sender);
		if (ENV == "SANDBOX") $this->mail->setRecipient("stefan@jutzi.net");
		else $this->mail->setRecipient($email);
		if ($this->replyto) { $this->mail->setReplyTo($this->replyto); }
		$this->mail->setMessage($message);
		$this->mail->setSubject($subject);
		$this->mail->render();
		foreach ($this->images AS $placeholder => $image_link) {
	        $this->mail->addImage($image_link, $placeholder);
		}
        $this->initialized = false;
		return $this->mail->send($html);
	}


}
