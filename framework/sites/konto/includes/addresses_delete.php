<?php
// Wenn Gast, dann sinnlos -> ab zur Übersichtsseite
$guest = $this->acl->checkRight(GUEST);
if ($guest) $this->app->forward("konto/register");

$id     = $this->request->args[1];

$a = new \Datacontainer\Fw_user_addresses($id);
if ($a->user_id != $this->app->getUser()->id) $this->app->forward("oops");
$a->delete();

$this->app->addSuccess("Die Adresse wurde erfolgreich gelöscht.");
$this->app->forward("konto/addresses");
