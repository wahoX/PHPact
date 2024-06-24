<?php
$url = $this->request->action;
$accessKey = $this->auth["key"];
$this->db->auth($this->auth);

$data = array();
$request = array();
switch ($this->request->method) {
    case "GET" :
        $request = $this->request->get;
        break;
    case "POST" :
        $request = $this->request->post;
        break;
    case "PUT" :
        $request = $this->request->put;
        break;
    case "DELETE" :
        $request = $this->request->delete;
        break;
}

$method = $this->request->method;
$now = $request["timestamp"];
reset($request);
foreach($request AS $k => $v) {
    $data[] = urlencode($k) . "=" . urlencode($v);
}

if (trim($now) == "") return "0";
$min = new \DateTime($now);
$min->sub(new \DateInterval('PT10H'));
$max = new \DateTime($now);
$max->add(new \DateInterval('PT10H'));
$now = new \DateTime();

$data = implode("&", $data);
$hmac = new \HmacAuth($url, $this->auth["key"], $this->auth["secret"], $method, $data);
$secret = $hmac->getSignature();

unset($this->auth["secret"]);

if (isset($request["debug"])) {
    $this->result->authdata = $this->auth;
    $this->result->method = $this->request->method;
    $this->result->get = $this->request->get;
    $this->result->post = $this->request->post;
    $this->result->put = $this->request->put;
    $this->result->delete = $this->request->delete;
    $this->result->server = $_SERVER;
}

?>
