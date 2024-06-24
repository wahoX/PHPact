<?php
/**
 * \namespace \
 */
	require_once 'Mail.php';
	require_once 'Mail/mime.php';

	class Email
	{
		private $sender;
		private $replyto;
		private $recipient;
        private $cc;
        private $bcc;
		private $subject;
		private $message;
		private $template = false;
		private $smtp = array();
		private $mail; // Das Mail-Objekt
	
		//-----------------------------------------------------------------------------
		public function __construct() {
			$this->sender = MAIL_Address;
			$this->smtp['host'] = MAIL_Host;
			$this->smtp['port'] = 587;
			$this->smtp['user'] = MAIL_User;
			$this->smtp['pass'] = MAIL_Pass;
			$this->mail = new Mail_Mime(PHP_EOL);

		}
		
		/**
		 * Setzen des Absenders
		 * 
		 * @param String $email Emailadresse des Absenders
		 * @param String $name (optional) Name des Absenders
		 */
		public function setSender($email, $name="") {
			if ($name != "") $this->sender = '"'.$name.'"'." <".$email.">";
			else $this->sender = $email;
		}
		
		/**
		 * Setzen der Antwort-Adresse
		 * 
		 * @param String $email Emailadresse der Antwort-Adresse
		 * @param String $name (optional) Name des Antwort-Adresse
		 */
		public function setReplyTo($email, $name="") {
			if ($name != "") $this->replyto = '"'.$name.'"'." <".$email.">";
			else $this->replyto = $email;
		}

		/**
		 * Setzen des Empfängers
		 * 
		 * @param String $email Emailadresse des Empfängers
		 * @param String $name (optional) Name des Empfängers
		 */
		public function setRecipient($email, $name="") {
			if ($name != "") $this->recipient = '"'.$name.'"'." <".$email.">";
			else $this->recipient = $email;
		}

		/**
		 * Setzen eines Kopie-Empfängers
		 * 
		 * @param String $email Emailadresse des Kopie-Empfängers
		 */
   		public function setCc($email) {
            $this->cc = $email;
        }

		/**
		 * Setzen eines Blindkopie-Empfängers
		 * 
		 * @param String $email Emailadresse des Blindkopie-Empfängers
		 */
   		public function setBcc($email) {
            $this->bcc = $email;
        }

		/**
		 * Setzen des Betreffs
		 * 
		 * @param String $subject Betreff der Email
		 */
		public function setSubject($subject) {
			$this->subject = $subject;
		}

		/**
		 * Setzen des Mailtextes
		 * 
		 * @param String $message Text der Email
		 */
		public function setMessage($message) {
			$this->message = $message;
		}

		/**
		 * Setzen des Templates
		 * 
		 * @param String $template Link zum Template-File
		 */
		public function setTemplate($template) {
			$this->template = new Parser($template);
		}
		
		/**
		 * Parst einen Bereich im Template
		 * 
		 * @param String $tag Tag im Template
		 * @param String $text Text, der gegen den Platzhalter ersetzt werden soll.
		 */
		public function parse($tag, $text) {
			if (!$this->template) die("Template wurde nicht definiert!");
			$this->template->parse($tag, $text);
		}

		/**
		 * Fügt ein einzubettendes Bild ein.
		 * 
		 * Um ein Bild einbinden zu können, braucht man Platzhalter im Template:
		 * <%LOGO_WIDTH%>, <%LOGO_HEIGHT%>, <%LOGO_ID%>
		 * 
		 * Kann z.B. so aussehen:
		 * <img style="float:right; width:<%LOGO_WIDTH%>px; height:<%LOGO_HEIGHT%>px;" src="cid:<%LOGO_ID%>">
		 * 
		 * @param String $image Pfad zum Bild
		 * @param String $placeholder Platzhalter im Template. Im oberen Bsp. wäre der zu übergebende Platzhalter "LOGO"
		 */
		public function addImage($image, $placeholder) {
			$this->template->parse($placeholder, $image);
			$this->mail->addHTMLImage($image, $this->getMimeType($image));
		}

		/**
		 * Hängt eine Datei an
		 * 
		 * @param String $file Pfad zur Datei
		 */
		public function addAttachment($file, $filename=false) {
			if (!$filename) $filename = basename($file);
			$this->mail->addAttachment($file, $this->getMimeType($file), $filename);
		}

		public function render() {
			if (!$this->template) die("Template wurde nicht definiert!");
			
			$this->template->setTemplate($this->template->getTemplate());
			$this->template->parse("SUBJECT", $this->subject);
			$this->template->parse("MESSAGE", $this->message);
			$this->template->setTemplate($this->template->getTemplate());

		}


		/**
		 * Absenden der Email
		 */
		public function send($html=true) {
			$this->render();
			$this->template->clearAll();

			$options = array(
				'auth'     => true,
				'host'     => $this->smtp['host'],
				'port'     => $this->smtp['port'],
				'username' => $this->smtp['user'],
				'password' => $this->smtp['pass'],
			);

			$mailer = Mail::factory('smtp', $options);		
			
			$text = $this->message;
			$text= str_replace("<br />","\n", $text);
			if ($html) $this->mail->setHTMLBody($this->template->getTemplate()); // Html-Body
			$this->mail->setTxtBody(html_entity_decode(strip_tags($text))."\n\n");

			$header = array();
			$header['Subject']      = $this->subject;         // Betreff setzen
			$now = new DateTime();
			$header['Date']      	= $now->format(DATE_RFC2822);
			$header['To']           = $this->recipient;   // Empfänger setzen
			if (ENV != "SANDBOX" && $this->cc != "") {
                $header['Cc'] = $this->cc;   // Empfänger setzen
                $this->recipient .= ",".$this->cc;
            }
			$header['From']         = $this->sender;   // Absender setzen
			$header['Reply-To']     = $this->replyto;
			$header['Message-ID']   = "<".md5(microtime()).".robot@bandarena.com>";
			$header['Charset']      = 'UTF-8';

			$header = $this->mail->headers($header);

            $body = $this->mail->get(array(
                            "text_charset"    => "UTF-8",
                            "html_charset"    => "UTF-8",
                            "head_charset"    => "UTF-8"));

			if ($this->bcc != "") $this->recipient .= ",".$this->bcc;   // BCC hinzufügen
//			if (ENV != "SANDBOX" && $this->bcc != "") $this->recipient .= ",".$this->bcc;   // BCC hinzufügen
			return $mailer->send($this->recipient, $header, $body);
		}



		//---------- Private (Hilfs-)Funktionen --------------------------
		
		/**
		 * Gibt den MimeType der Datei zurück
		 * 
		 * @param String $file Name der Datei
		 * @return String MimeType der Datei
		 */
		private function getMimeType($file) {
			$mime_types = array(

				'txt' => 'text/plain',
				'htm' => 'text/html',
				'html' => 'text/html',
				'php' => 'text/html',
				'css' => 'text/css',
				'js' => 'application/javascript',
				'json' => 'application/json',
				'xml' => 'application/xml',
				'swf' => 'application/x-shockwave-flash',
				'flv' => 'video/x-flv',

				// images
				'png' => 'image/png',
				'jpe' => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'jpg' => 'image/jpeg',
				'gif' => 'image/gif',
				'bmp' => 'image/bmp',
				'ico' => 'image/vnd.microsoft.icon',
				'tiff' => 'image/tiff',
				'tif' => 'image/tiff',
				'svg' => 'image/svg+xml',
				'svgz' => 'image/svg+xml',

				// archives
				'zip' => 'application/zip',
				'rar' => 'application/x-rar-compressed',
				'exe' => 'application/x-msdownload',
				'msi' => 'application/x-msdownload',
				'cab' => 'application/vnd.ms-cab-compressed',

				// audio/video
				'mp3' => 'audio/mpeg',
				'qt' => 'video/quicktime',
				'mov' => 'video/quicktime',
	
				// adobe
				'pdf' => 'application/pdf',
				'psd' => 'image/vnd.adobe.photoshop',
				'ai' => 'application/postscript',
				'eps' => 'application/postscript',
				'ps' => 'application/postscript',
	
				// ms office
				'rtf' => 'application/rtf',
				'doc' => 'application/msword',
				'docx' => 'application/msword',
				'xls' => 'application/vnd.ms-excel',
				'xlsx' => 'application/vnd.ms-excel',
				'ppt' => 'application/vnd.ms-powerpoint',
				'pps' => 'application/vnd.ms-powerpoint',
				'pptx' => 'application/vnd.ms-powerpoint',
	
				// open office
				'odt' => 'application/vnd.oasis.opendocument.text',
				'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
			);
			
			$ext = strtolower(array_pop(explode('.',$file)));
			if (array_key_exists($ext, $mime_types)) {
				return $mime_types[$ext];
			} else {
				return 'application/octet-stream';
			}

		}

	}
?>
