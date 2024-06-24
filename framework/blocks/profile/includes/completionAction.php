<?php
        $user = $this->app->getUser();
        $percent = 0;
        $missing = array();
        
        $tplMissing = $this->getSubTemplate("MISSING");

        if (trim($user->surname) != "") { $percent += 5; }
        else { $missing[] = "Nachname: 5%"; }
        
        if (trim($user->forename) != "") { $percent += 5; }
        else { $missing[] = "Vorname: 5%"; }

        if (count($user->addresses) > 0) { $percent += 10; }
        else { $missing[] = "Adresse: 10%"; }
          
        if (\Utils::validateEmail($user->email)) { $percent += 5; }
        else { $missing[] = "E-Mail Adresse: 5%"; }
        
        $phone = false;
        if (isset($user->extradata["telefon"]["value"]) && trim($user->extradata["telefon"]["value"]) != "") { $phone = true; }
        else {
            foreach($user->addresses AS $a) {
                if (trim($a["phone"]) != "") $phone = true;
            }
        }
        if (phone) { $percent += 10; }
        else { $missing[] = "Telefonnummer: 10%"; }
        
        if (isset($user->extradata["web"]["value"]) && trim($user->extradata["web"]["value"]) != "") { $percent += 5; }
        else { $missing[] = "Webseite: 5%"; }
        
        if (trim($user->avatar) != "") { $percent += 20; }
        else { $missing[] = "Profilbild: 20%"; }
        
        if ($user->gender > 0) { $percent += 5; }
        else { $missing[] = "Geschlecht: 5%"; }
        
        if (trim($user->facebook_id) != "") { $percent += 10; }
        else { $missing[] = "Verknüpfen Sie Ihr Profil mit Ihrem Facebook-Account: 10%"; }
        
        if (isset($user->extradata["arbeitgeber"]["value"]) && trim($user->extradata["arbeitgeber"]["value"]) != "") { $percent += 10; }
        else { $missing[] = "Informationen zum Arbeitgeber: 5%"; }
        
        if (isset($user->extradata["hobby"]["value"]) && trim($user->extradata["hobby"]["value"]) != "") { $percent += 5; }
        else { $missing[] = "Hobbies: 5%"; }
        
        $im = false;
        if (isset($user->extradata["aim"]["value"]) && trim($user->extradata["aim"]["value"]) != "") { $im = true; }
        else if (isset($user->extradata["icq"]["value"]) && trim($user->extradata["icq"]["value"]) != "") { $im = true; }
        else if (isset($user->extradata["msn"]["value"]) && trim($user->extradata["msn"]["value"]) != "") { $im = true; }
        else if (isset($user->extradata["skype"]["value"]) && trim($user->extradata["skype"]["value"]) != "") { $im = true; }
        if ($im) { $percent += 5; }
        else { $missing[] = "Messenger zur Kontaktaufnahme: 5%"; }
        
        if (trim($user->birthday) != "") { $percent += 5; }
        else { $missing[] = "Geburtstag: 5%"; }
        
        foreach($missing AS $m) {
            $tplMissing->TEXT = $m;
            $this->MISSING .= $tplMissing->render();
            $tplMissing->resetParser();
        }
        $this->PERCENT = $percent;
        
        if ($percent >= 100) $this->setTemplate("");
