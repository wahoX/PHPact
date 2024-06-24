<?php
if (!$this->acl->checkRight(4) && !$this->acl->checkRight(5)) {
    $this->app->forward("404");
}

if (intval($this->request->post["test"]) != 0) {
    $tpl = intval($this->request->post["test"]);
    $user = $this->app->getUser();
    $email = $this->request->post["email"];
    $mail = $this->app->getModule("Email_v2");
    $mail->addImage(FRAMEWORK_DIR . "res/images/mail/user_header.jpg", "HEADER_IMAGE");
    $mail->addImage(FRAMEWORK_DIR . "res/images/mail/footer.jpg", "FOOTER_IMAGE");
    $now = new \DateTime();
    $values = array(
        "USERNAME" => $user->forename." ".$user->surname,
        "LINK" => "http://NrEins.de/",
        "MITGLIEDSNUMMER" => $user->id,
        "EINLADER" => "Max Muster",
        "GUELTIGBIS" => $now->format("d.m.Y"),
        "BANK" => "Im Anschluss leiten wir Ihre Daten an unsere Partner-Bank, die Advanzia-Bank S.A., weiter. Sie werden von unserer Partnerbank bez&uuml;glich Ihres Kartenantrages per E-Mail informiert.<br /><br />",
    );
    
    $mail->send($email, "Test-E-Mail", $tpl, $values);
    $this->app->forward($this->request->referrer);
}

if (intval($this->request->post["id"]) != 0) {
    $id = intval($this->request->post["id"]);
    $template = $this->request->post["template"];
    $this->db->saveMailTemplate($id, $template);
    $this->app->addSuccess("Das Template wurde gespeichert.");
    $this->app->forward($this->request->referrer);
}

$id = intval($this->request->args[0]);
$tplForm = $this->getSubtemplate("FORM");
$tplOption = $this->getSubtemplate("OPTION");
$tplTestmail = $this->getSubtemplate("TESTMAIL");

if ($id != 0) {
    $this->app->registerJs("res/js/codemirror/lib/codemirror.js");
    $this->app->registerJs("res/js/codemirror/mode/htmlmixed/htmlmixed.js");
    $this->app->registerJs("res/js/codemirror/mode/xml/xml.js");
    $this->app->registerJs("res/js/codemirror/mode/css/css.js");
    $this->app->registerJs("res/js/codemirror/mode/javascript/javascript.js");
    $this->app->registerCss("res/js/codemirror/lib/codemirror.css");
    $this->app->registerCss("res/js/codemirror/theme/xq-light.css");
    $this->app->registerCss(".CodeMirror { border:1px solid #888; }", true);
    $this->app->registerOnload("
          var mixedMode = {
            name: 'htmlmixed',
            scriptTypes: [{matches: /\/x-handlebars-template|\/x-mustache/i,
                           mode: null},
                          {matches: /(text|application)\/(x-)?vb(a|script)/i,
                           mode: 'vbscript'}]
          };
          var editor = CodeMirror.fromTextArea(document.getElementById('code'), {
            mode: mixedMode,
            tabMode: 'indent',
            lineNumbers: true
          });
          editor.setSize(950,600);
          editor.setOption('theme', 'xq-light');
    ");
}

$this->app->registerJs("
    function changeTemplate() {
        var id = parseInt($('#select_template').val());
        if (id != 0) document.location.href = '[[LinkTo:admin/mailtemplates/]]' + id;
    }
", true);

$templates = $this->db->getMailTemplate();
#print_r($templates);die;
foreach($templates AS $t) {
    $tplOption->ID = $t["id"];
    $tplOption->NAME = htmlentities(\Utils::str_encode($t["name"], "ISO-8859-1"));
    if ($t["id"] == $id) {
        $tplOption->SELECTED = " selected='selected'";
        $tplForm->ID = $t["id"];
        $tplForm->NAME = $t["name"];
        $tplForm->TEMPLATE_VALUE = htmlentities(\Utils::str_encode($t["template"], "ISO-8859-1"));
        $tplForm->LEGENDE = nl2br($t["legende"]);
        $this->FORM = $tplForm->render();
        $tplTestmail->ID = $t["id"];
        $this->TESTMAIL = $tplTestmail->render();
    }
    $this->OPTIONS .= $tplOption->render();
    $tplOption->resetParser();
}

?>
