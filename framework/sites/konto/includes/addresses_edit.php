<?php
$this->app->addLocation($this->request->url, "Adresse bearbeiten");

// Wenn Gast, dann sinnlos -> ab zur Übersichtsseite
$guest = $this->acl->checkRight(GUEST);
if ($guest) $this->app->forward("konto/register");

$this->app->registerCSS("res/css/user.css");

$action = $this->request->args[0];
$id     = $this->request->args[1];
$userID = $this->app->getUser()->id;

$tplAddress = new \Controller();
$tplAddress->setTemplate($this->parseTag("[FORM]"));
$tplAddress->FORM_ACTION = $action;			

$tplHeadline = new \Controller();
$tplHeadline->setTemplate($this->parseTag("[FORM_EDIT_HEADLINE]"));

$tplAddress->ADDRESSES_HEADLINE = $tplHeadline->render();	

$form = new \Form($tplAddress, "addresses_form");
$form->registerElement("rowid", "number", true, ""); 	                                            					// Datensatz ID			
$form->registerElement("shortcut", "any", true, "Bitte gib eine Beschreibung für die Adresse an! Erlaubte Zeichen: a-z, A-Z"); // Beschreibung	
$form->registerElement("title", "select", true, ""); 	                                            					// Anrede					
$form->registerElement("name", "any", true, "Bitte gib einen Namen ein! Erlaubte Zeichen: a-z, A-Z");	      			// Name
$form->registerElement("forename", "any", true, "Bitte gib einen Vornamen ein! Erlaubte Zeichen: a-z, A-Z"); 			// Vorname
$form->registerElement("company", "any", false, "");                                            						// Firma
$form->registerElement("city", "any", true, "Bitte gib einen Stadtnamen ein! Erlaubte Zeichen: a-z, A-Z"); 	    		// Stadt
$form->registerElement("position", "any", false, "");																	// Lage / Etage					
$form->registerElement("zip", "number", true, "Bitte gib eine Postleitzahl ein! Erlaubte Zeichen: 0-9");// PLZ
$form->registerElement("street", "any", true, "Bitte gib eine Straße ein! Erlaubte Zeichen: 0-9");         				// Strasse
$form->registerElement("street_number", "any", true, "Bitte gib eine Hausnummer ein! Erlaubte Zeichen: a-z, A-Z, 0-9"); // Hausnr.
$form->registerElement("email", "email", false, "Bitte gib eine gültige E-Mail-Adresse ein!");// E-Mailadresse
$form->registerElement("phone", "phone", false, "Bitte gib eine Telefonnummer ein! Erlaubte Zeichen: 0-9");             // Vorwahl
$form->registerElement("prephone", "phone", false, "Bitte gib eine Vorwahl ein! Erlaubte Zeichen: 0-9");                // Telefon
$form->registerElement("is_standard", "checkbox", false);                                         						// Standard Lieferadresse

$a = new \Datacontainer\Fw_user_addresses($id);
if ($a->user_id != $this->app->getUser()->id) $this->app->forward("oops");

if ($tplAddress->request->post["send"] != "")
{
    $form->setFormData($this->request->post);
    $success = $form->run();


    if ($success) 
    {
        $post = $this->request->post;
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
        
        $this->app->addSuccess("Die Adresse wurde geändert.");				
        $this->app->forward("konto/addresses");
    }
}

if ($a->title == 1) $tplAddress->TITLE_SELECTED_1 = "checked=\"checked\"";
if ($a->title == 2) $tplAddress->TITLE_SELECTED_2 = "checked=\"checked\"";
$tplAddress->ROWID_VALUE = $a->id;
$tplAddress->SHORTCUT_VALUE = $a->address_title;
$tplAddress->NAME_VALUE = $a->name;
$tplAddress->FORENAME_VALUE = $a->forename;
$tplAddress->COMPANY_VALUE = $a->company;
$tplAddress->STREET_VALUE = $a->street;
$tplAddress->STREET_NUMBER_VALUE = $a->street_number;
$tplAddress->POSITION_VALUE = $a->position;
$tplAddress->ZIP_VALUE = $a->zip;
$tplAddress->CITY_VALUE = $a->city;
$tplAddress->PREPHONE_VALUE = $a->prephone;
$tplAddress->PHONE_VALUE = $a->phone;
$tplAddress->EMAIL_VALUE = $a->email;
$tplAddress->IS_STANDARD_VALUE = $a->is_standard;        

$tplAddress->STANDARD_CHECKED = ($a->is_standard == "1") ? "checked=\"checked\"" : "" ;

