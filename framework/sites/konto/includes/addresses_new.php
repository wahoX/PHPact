<?php
$this->app->addLocation($this->request->url, "neue Adresse anlegen");
// Wenn Gast, dann sinnlos -> ab zur Übersichtsseite
$guest = $this->acl->checkRight(GUEST);
if ($guest) $this->app->forward("konto/register");

$this->app->registerCSS("res/css/user.css");

$action = $this->request->args[0];

#$tplForm = new \Controller(__DIR__."/addresses.html", "[FORM]");
$tplForm = new \Controller();
$tplForm->setTemplate($this->parseTag("[FORM]"));
$tplForm->FORM_ACTION = $action;

$tplHeadline = new \Controller();
$tplHeadline->setTemplate($this->parseTag("[FORM_NEW_HEADLINE]"));

$tplForm->ADDRESSES_HEADLINE = $tplHeadline->render();	

$form = new \Form($tplForm, "addresses_form");
$form->registerElement("shortcut", "any", true, "Bitte gib eine Beschreibung für die Adresse an! Erlaubte Zeichen: a-z, A-Z"); // Beschreibung	
$form->registerElement("title", "select", true, ""); 	                                            					// Anrede					
$form->registerElement("name", "any", true, "Bitte gib einen Namen ein! Erlaubte Zeichen: a-z, A-Z");	      			// Name
$form->registerElement("forename", "any", true, "Bitte gib einen Vornamen ein! Erlaubte Zeichen: a-z, A-Z");			// Vorname
$form->registerElement("company", "any", false, "");                                            						// Firma
$form->registerElement("city", "any", true, "Bitte gib einen Stadtnamen ein! Erlaubte Zeichen: a-z, A-Z"); 	    		// Stadt
$form->registerElement("zip", "plz", true, "Bitte gib eine gültige Postleitzahl ein!");			// PLZ
$form->registerElement("position", "any", false, "");																	// Lage / Etage		
$form->registerElement("street", "any", true, "Bitte gib eine Straße ein! Erlaubte Zeichen: 0-9");         				// Strasse
$form->registerElement("street_number", "any", true, "Bitte gib eine Hausnummer ein! Erlaubte Zeichen: a-z, A-Z, 0-9"); // Hausnr.
$form->registerElement("email", "email", false, "Bitte gib eine gültige E-Mail-Adresse ein!"); // E-Mailadresse
$form->registerElement("phone", "phone", false, "Bitte gib eine Telefonnummer ein! Erlaubte Zeichen: 0-9");             // Vorwahl
$form->registerElement("prephone", "phone", false, "Bitte gib eine Vorwahl ein! Erlaubte Zeichen: 0-9");                // Telefon
$form->registerElement("is_standard", "checkbox", false);                                         						// Standard Lieferadresse

if ($tplForm->request->post["send"] != "") {
    $form->setFormData($this->request->post);


    $success = $form->run();
    if ($success) 
    {
        $post = $this->request->post;
        $a = new \Datacontainer\Fw_user_addresses();
        $a->user_id = $this->app->getUser()->id;
        $a->address_title = $post["shortcut"];
        $a->title = $post["title"];
        $a->name = $post["name"];
        $a->forename = $post["forename"];
        $a->company = $post["company"];
        $a->street = $post["street"];
        $a->street_number = $post["street_number"];
        $a->position = $post["position"];				
        $a->zip = $post["zip"];
        $a->city = $post["city"];
        $a->email = $post["email"];
        $a->prephone = $post["prephone"];
        $a->phone = $post["phone"];
        $a->is_standard	= intval($post["is_standard"]);

        if(isset($this->request->post["is_standard"]))
        {
            $addresses = $this->query("fw_user_addresses")->where("user_id = ".$this->app->getUser()->id)->asArray()->run();
            foreach ($addresses AS $address) {
                $tmp = new \Datacontainer\Fw_user_addresses($address["id"]);
                $tmp->is_standard = 0;
                $tmp->save();
            }
        }

        $a->save();
        $this->app->addSuccess("Die neue Adresse wurde angelegt.");				
        $this->app->forward("konto/addresses");
    }
}
