<?php
namespace Module;
/**
 * Das Modul Email versendet E-Mails an User.
 */
class Email_v2 extends \Module
{
	private $sender = "";
	private $mail;
    private $initialized = false;
    private $images = array();
    private $placeholders = array();
    private $ignore_sandbox = false;
	
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

		$this->mail = new \Email();
		$this->mail->setTemplate(__DIR__."/tpl.html");
	}

	public function setMastertemplate($file) {
		$this->init();
		$this->mail->setTemplate($file);
	}

    public function addAttachment($file) {
        $this->init();  
        $this->mail->addAttachment($file);
    }
    
    public function addImage($image_link, $placeholder) {
		$this->images[$placeholder] = $image_link;
    }

    public function addValue($placeholder, $value) {
		$this->placeholders[$placeholder] = $value;
    }
    
    public function setCc($email) {
        $this->init();  
        $this->mail->setCc($email);
    }

    public function setBcc($email) {
        $this->init();  
        $this->mail->setBcc($email);
    }
    
    public function ignore_sandbox() {
        $this->ignore_sandbox = true;
    }
    
    public function setReplyTo($email) {
        $this->init();  
        $this->mail->setReplyTo($email);
    }

    /**
     * Diese Funktion verschickt eine E-Mail.
     *
     * @param String $email E-Mail Adresse des Empfängers
     * @param String $subject Betreff der E-Mail
     * @param Integer $template ID des Templates in mail_templates
     * @param Array $placeholders Werte für die Platzhalter als assoziatives Array (Key: Platzhalter, Value: Wert)
     * @param Boolean $html true wenn HTML, false wenn nur Text
     * @param String $sender Absender der E-Mail
     * @param String $cc Empfänger einer Kopie - mehrere werden mit Komma getrennt
     * @param String $bcc Empfänger einer Blind-Kopie - mehrere werden mit Komma getrennt
     * 
     * @return mixed true, wenn erfolgreich. Object, wenn nicht erfolgreich.
     **/
    public function send($email, $subject, $template, $placeholders=array(), $html=true, $sender=null, $cc=null, $bcc=null)
    {
        if (!$sender) $sender = "Timetracker <timetracker@jutzi.net>";
        $this->init();
        
        if (ENV == "SANDBOX" && !$this->ignore_sandbox) {
            $user = \Application::getInstance()->getUser();
            if ($user->isLoggedIn()) $email = $user->email;
            else $email = "jutzi@wahox.de";
        }
        $this->mail->setRecipient($email);
        if ($cc != "") $this->mail->setCc($cc);
        if ($bcc != "") $this->mail->setBcc($bcc);

        foreach ($placeholders AS $p => $v) {
            $this->placeholders[$p] = $v;
        }
        $tpl = \DB::getInstance()->getMailTemplate($template);
        $c = new \Controller();
        $c->setTemplate($tpl);

        $user_id = $this->placeholders["MITGLIEDSNUMMER"];
        if (!$user_id) $user_id = \Application::getInstance ()->getUser()->id;
        // Wenn Template 3/4, dann Werbung für AS (4), ansonsten User (3)
        $type = ($template == 3 || $template == 4 || $template == 10) ? 4 : 3;
        $args = array("type" => $type, "user_id" => $user_id);

        if (stristr($c->getTemplate(), "<%WERBUNG%>")) {
            $ad = new \Block("Ad", "email", $args);
            if ($ad->getTemplate() != "") {
                $this->placeholders["WERBUNG"] = $ad->render();
                $this->addImage(urldecode($ad->getValue("IMAGE_URL")), "AD_SIDE_URL");
                #print_r($ad->getValue("IMAGE_URL"));die;
            }
	}
        foreach($this->placeholders AS $p => $v) {
            $c->$p = $v;
        }

        $this->mail->setSender($sender);
	$this->mail->setMessage($c->render());
	$this->mail->setSubject($subject);
	$this->mail->render();
        foreach ($this->images AS $placeholder => $image_link) {
	    $this->mail->addImage($image_link, $placeholder);
	}

        $this->initialized = false;
        $this->placeholders = array();
        $this->images = array();
        
        return $this->mail->send($html);
    }
}
