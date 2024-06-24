<?php
$this->app->addLocation($this->request->url, "Adressen verwalten");

// Wenn Gast, dann sinnlos -> ab zur Übersichtsseite
$guest = $this->acl->checkRight(GUEST);
if ($guest) $this->app->forward("konto/register");

$this->app->registerCSS("res/css/addresses-mobile.css");

$action = $this->request->args[0];
switch ($action) {
    case "new" : $tpl = $this->addresses_new(); break;
    case "edit" : $tpl = $this->addresses_edit(); break;
    case "delete" : $tpl = $this->addresses_delete(); break;
    case "set_standard" : $tpl = $this->addresses_set_standard(); break;
    default:
    $tpl = new \Controller();
    $tpl->setTemplate($this->parseTag("[LIST]"));

    $tplAddress = new \Controller();
    $tplAddress->setTemplate($this->parseTag("[ADDRESS]"));

    $addresses = $this->query("fw_user_addresses")->where("user_id = ".$this->app->getUser()->id)->order("is_standard DESC, title")->asArray()->run();

    foreach ($addresses as $a) {
        if($a["is_standard"] == 1) {
            $tplAddress->IS_STANDARD_SHOW = "none";
            $tplAddress->CSS_CLASS = "warning";
        }
        else {
            $tplAddress->IS_STANDARD_HIDE = "none";
            $tplAddress->CSS_CLASS = "default";
        }
        $tplAddress->SHORTCUT = $a["address_title"];
        $tplAddress->NAME = $a["name"];
        $tplAddress->FORENAME = $a["forename"];   
        $tplAddress->COMPANY = $a["company"];
        $tplAddress->STREET = $a["street"];          
        $tplAddress->STREETNUMBER = $a["street_number"];
        $tplAddress->ZIP = $a["zip"];
        $tplAddress->POSITION = $a["position"];				
        $tplAddress->CITY = $a["city"];
        $tplAddress->PREPHONE = $a["prephone"];
        $tplAddress->PHONE = $a["phone"];
        $tplAddress->EMAIL = $a["email"];
        $tplAddress->ROWID = $a["id"];

        $tpl->ADDRESSES .= $tplAddress->render();

        $tplAddress->resetParser();
    }
}
$this->CONTENT = $tpl->render();
