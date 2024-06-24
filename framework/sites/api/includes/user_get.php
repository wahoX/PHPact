<?php
if ($this->request->get["username"] && $this->request->get["password"]) {
    $return = new \stdClass();

    $this->request->cookie = array(
		"username" => $this->request->get["username"],
		"password" => md5($this->request->get["password"])
	);
    $this->app->autologin(false);
    $this->acl->init();
	$user = $this->app->getUser();
    
//    $access = $this->app->login($this->request->get["username"], $this->request->get["password"]);
    $this->app->disableRender();
    if ($user->isLoggedIn()) {
        $this->acl->init();
        $this->result->Success = "1";
        $this->result->user = new \StdClass;
        $this->result->user->id = $user->id;
        $this->result->user->forename = $user->forename;
        $this->result->user->surname = $user->surname;
        $this->result->user->birthday = $user->birthday;
        $gender = "unknown";
        if ($user->gender == "1") $gender = "male";
        if ($user->gender == "2") $gender = "female";
        $this->result->user->gender = $gender;
        $this->result->user->email = $user->email;
        $this->result->user->editable = ($user->created_by == $this->auth["user"]) ? "1" : "0";
        $this->result->user->extradata = new \StdClass;
        foreach ($user->extradata AS $k=>$v) {
            $this->result->user->extradata->$k = $v["value"];
        }
        $actions = array();
        foreach($this->acl->actions AS $k => $v) {
			$actions[] = $k;
		}
		$this->result->Aktionen = $actions;
    } else {
        $this->result->Success = "0";
        $this->result->Error = "Die angegebenen Benutzerdaten sind falsch.";
        $this->result->Errorcode = "1";
   }
} else {
    $this->result->Success = "0";
    $this->result->Error = "Die angegebenen Benutzerdaten sind falsch.";
    $this->result->Errorcode = "1";
}
?>
