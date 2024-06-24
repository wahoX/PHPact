<?php
$this->app->addLocation($this->request->url, "Bestellungen verwalten");

// Wenn Gast, dann sinnlos -> ab zur Übersichtsseite
$guest = $this->acl->checkRight(GUEST);
if ($guest) $this->app->forward("konto/register");

$user = $this->app->getUser();

if ($this->request->args[0] == "import") {
	$order_id = intval($this->request->args[1]);
	$product_id = intval($this->request->args[2]);
	if ($order_id > 0) {
		$order = new \Datacontainer\Bestellungen($order_id);
		if ($order->id) {
			$cart = json_decode($order->export)->cart;
			$product = $cart->products[$product_id];
			if ($product) {
				//print_r($product);die;
				$p = new \Produkt($product->id, $product->sizeid);
				if (!$p->isActive() || !$p->isAvailable() || !$p->isVisible()) {
					$this->app->addError("Das gewählte Produkt ist zur Zeit nicht verfügbar.");
					$this->app->forward("konto/orders");
				}
				//print_r($p);die;
				foreach($product->addons AS $a) {
					if ($a->id) {
						for ($i=1; $i<=$a->num; $i++) $p->add_zutat($a->id);
					} else {
						$p->add_zutat(str_replace("ohne ", "omit_", $a->name));
					}
				}
				$this->cart->push_item($p);
				$this->app->addSuccess("Das Produkt wurde 1x in die Einkaufstüte gelegt.");
				$this->app->forward("konto/orders");
			}
		}
	}
}

$tplOrder = $this->getSubtemplate("ORDER");
$tplProduct = $this->getSubtemplate("PRODUCT");

$count_orders = 0;
$orders = $this->query("bestellungen")->where("kundeID = ".$user->id)->order("id DESC")->limit(10)->asArray()->run();
foreach($orders AS $o) {
	$o = (object) $o;
	$export = json_decode($o->export);
	$tplOrder->ZEIT = (new \DateTime($o->created_at))->format("d.m.Y");
	$tplOrder->USER = (array) $export->user;
	foreach($export->cart->products AS $k => $p) {
		$tplProduct->NUM = $p->num;
		$tplProduct->NAME = str_replace(", Standard", "", $p->name);
		$addons = array();
		$tmpPrice = $p->price;
		foreach($p->addons AS $a) {
			$addons[] = "+ ".$a->num."x ".$a->name."<br />";
			$tmpPrice += $a->num * $a->price;
		}
		if (count($addons) > 0) {
			$tplProduct->ADDONS = implode("", $addons);
		}
		$tplProduct->ORDER_ID = $o->id;
		$tplProduct->PRODUCT_ID = $k;
		$tplProduct->PRICE = \Utils::price($p->num * $tmpPrice);
		$tplOrder->PRODUCTS .= $tplProduct->render(true);
	}
	
	if ($export->cart->zahlgebuehr) {
		$tplProduct->HIDDEN = "hidden";
		$tplProduct->NUM = 1;
		$tplProduct->NAME = "Gebühr für Zahlart";
		$tplProduct->PRICE = \Utils::price($export->cart->zahlgebuehr);
		$tplOrder->PRODUCTS .= $tplProduct->render(true);
	}
	$tplOrder->PRICE = \Utils::price($export->cart->preis);
	$this->ORDERS .= $tplOrder->render(true);
	$count_orders++;
}

if (!$count_orders) $this->ORDERS = "<h4>Im System sind keine Bestellungen von Ihnen gespeichert.</h4>";

