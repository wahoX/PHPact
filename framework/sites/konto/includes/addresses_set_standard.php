<?php

// Wenn Gast, dann sinnlos -> ab zur Übersichtsseite
$guest = $this->acl->checkRight(GUEST);
if ($guest) $this->app->forward("konto/register");

$id = $this->request->args[1];

$a = new \Datacontainer\Fw_user_addresses($id);
if ($a->user_id != $this->app->getUser()->id) $this->app->forward("oops");
$a->is_standard = 1;

$addresses = $this->query("fw_user_addresses")->where("user_id = ".$this->app->getUser()->id)->asArray()->run();
foreach ($addresses AS $address) {
    $tmp = new \Datacontainer\Fw_user_addresses($address["id"]);
    $tmp->is_standard = 0;
    $tmp->save();
}
$a->save();

$this->app->forward("konto/addresses");		
    
