<?php
namespace Blocks;
/**
 * Die Controller-Klasse beinhaltet allgemeine Controller-Funktionen.
 *
 * Alle Klassen in sites erben von dieser Controller-Klasse.
 */
class Datatable extends \Controller
{
    
    /*
     * @param Object $args - Daten für die Tabelle
     * Erwartete Argumente:
     * - header als Array mit folgenden Infos für jede Spalte:
     *   - title: Titel - Pflicht
     *   - searchable: Durchsuchbar? - Pflicht
     *   - field: Nimm Daten aus diesem Feld aus data - optional
     *   - type: Datentyp
     *     - int
     *     - float:2 (Hinterer teil gibt Nachkommsstellen an)
     *     - date
     *     - datetime
     * - data : Array mit den Daten - \Query("table")->asArray()->run();
     * - table : Name der tabelle, aus der die Daten genommen werden sollen
     * - callback : URL des Ajax-Callbacks
     * - tableoptions: Weitere Optionen für die Tabelle als assoziatives Array.
     *   Bsp: $o->tableoptions = array("iDisplayLength" => 50);
     * 
     * Es muss entweder data, table oder callback (genau eines der 3) angegeben sein.
     * 
     */
	public function indexAction($args)
	{
        $this->app->registerJs("res/js/jquery/jquery.dataTables.min.js");
        $this->app->registerJs("res/js/jquery/datatable/ZeroClipboard.js");
        $this->app->registerJs("res/js/jquery/datatable/datatable.tabletools.min.js");
        $this->app->registerJs("res/js/jquery/datatable/dataTables.bootstrap.js");
        $this->app->registerJs("res/js/jquery/datatable/dataTables.filterdelay.js");
        $this->app->registerJs("res/js/jquery/datatable/dataTables.resetfilter.js");
        $this->app->registerCss("res/css/jquery.dataTables.css");
        $url = "";
        if (isset($args->table)) {
            $url = "ajax/blocks/get/datatable/table/".$args->table;
        }
        if (isset($args->callback)) {
            $url = $args->callback;
        }
        if ($url != "") {
            $a = clone $args;
            unset($a->data);
            $tableoptions = "
                var zusatz = {
                    'bProcessing': true,
                    'bServerSide': true,
                    'sAjaxSource': '/ajax/blocks/get/datatable/data/',
                    'sServerMethod' : 'POST',
                    'fnServerParams' : function ( aoData ) {
                        aoData.push( { 'name' : 'args' , 'value' : '".  base64_encode(json_encode($a))."' } );
                    }
                }
                $.extend(options, zusatz);
            ";
        }
        $this->app->registerJs("
            var oTable;
        ", true);

        $tplTh2 = $this->getSubTemplate("TH2");
        $tplHeadSearchable = $this->getSubTemplate("TH_SEARCHABLE");
        $tplHeadNotSearchable = $this->getSubTemplate("TH_NOT_SEARCHABLE");
        $tplHeadSelect = $this->getSubTemplate("TH_SELECT");
        $tplHeadOption = $this->getSubTemplate("TH_OPTION");
        $head = $args->header;
        $aoColumns = array();
        $cookies = $this->request->cookie;
        $search = array();
        foreach($cookies AS $k => $v) {
            $action = $this->request->action;
            if ($action == "") $action = $this->request->site;
            if (stristr($k, "DataTables") && stristr($k, $action)) {
                $cookie = json_decode($v);
                $search = $cookie->aoSearchCols;
            }
        }
        $count=0;
        foreach($head AS $h) {
            if ($h->searchable && isset($h->options)) {
                foreach($h->options AS $name => $wert) {
                    $tplHeadOption->NAME = $name;
                    $tplHeadOption->VALUE = $wert;
                    $tplHeadSelect->OPTIONS .= $tplHeadOption->render();
                    $tplHeadOption->resetParser();
                }
                $tplHeadSelect->NAME = $h->title;
                if ($h->width) $tplHeadSelect->STYLE = "width:".$h->width.";";
                $this->THEAD1 .= $tplHeadSelect->render();
                $tplHeadSelect->resetParser();
            } else {
                $tpl = ($h->searchable) ? $tplHeadSearchable : $tplHeadNotSearchable;
                $tpl->NAME = $h->title;
                if ($h->width) $tpl->STYLE = "width:".$h->width.";";
                if (isset($search[$count])) {
                    $tpl->SEARCH_VALUE = $search[$count]->sSearch;
                }
                $this->THEAD1 .= $tpl->render();
                $tpl->resetParser();
            }
            $tplTh2->NAME = $h->title;
            $this->THEAD2 .= $tplTh2->render();
            $tplTh2->resetParser();
            $aoColumns[] = ($h->sortable == false) ? "{'bSortable': false}" : "{}";
            $count++;
        }
        $tmpoptions = "";
        if (is_array($args->tableoptions)) {
            foreach($args->tableoptions AS $k => $v) {
                $tmpoptions .= "'".$k."': '".$v."', ";
            }
        }
        $tableoptions .= "
            var zusatz2 = {
                ".$tmpoptions."
                'sDom' : '<\"H\"RCTlip><rt><\"F\"lp>',
                'aoColumns': [ ".implode(",", $aoColumns)." ],
                'tableTools': {
                    'sSwfPath': '/res2/flash/copy_csv_xls_pdf.swf'
                }
            }
           $.extend(options, zusatz2);
        ";
        
        $data = $args->data;
        $tplRow = $this->getSubTemplate("ROW");
        $tplCol = $this->getSubTemplate("COL");
        
        $rows = $this->buildData($data, $head);
        foreach($rows AS $r) {
            foreach ($r AS $content) {
                $tplCol->DATA = $content;
                $tplRow->COLS .= $tplCol->render();
                $tplCol->resetParser();
            }
            $this->ROWS .= $tplRow->render();
            $tplRow->resetParser();
        }
        
        $this->app->registerOnload("
            var options = dataTableOptions;".$tableoptions."
            var asInitVals = [];	
            oTable = $('.datatable').dataTable(options).fnSetFilteringDelay(300);
            
            var header_inputs = $('.datatable thead input');
            header_inputs.on('keyup', function(){
                oTable.fnFilter( this.value, header_inputs.index(this) );
            });
            header_inputs.each( function (i) {
                asInitVals[i] = this.value;
            });
        ");
        
	}
    
    public function dataAction() {
        $this->setTemplate("");
        $args = json_decode(base64_decode($this->request->post["args"]));
        $data = $this->loadData($args);
        
        $result = new \stdClass();
        $result->sEcho = $this->request->post["sEcho"];
        $result->iTotalRecords = $data[0];
        $result->iTotalDisplayRecords = $data[0];
        $result->aaData = $this->buildData($data[1], $args->header);
        $this->app->addAjaxContent(json_encode($result));
        
    }
    
    /**
     * Diese Funktion lädt die Daten aus einer Tabelle (Wenn args->table angegeben ist)
     * 
     * @param String $tablename Name der Tabelle
     * @param Objekt $args Die in indexAction übergebenen Argumente (ohne data)
     * @return Array(Gesamtanzahl der Datensätze, Daten)
     */
    private function loadData($args) {
        $start = intval($this->request->post["iDisplayStart"]);
        $count = intval($this->request->post["iDisplayLength"]);
        if ($count < 0) $count = 9999999;
        if ($count > 0) $limit = "LIMIT ".$start.", ".$count;

        $orderField = intval($this->request->post["iSortCol_0"]);
        $dir = ($this->request->post["sSortDir_0"] == "asc") ? "ASC" : "DESC";
        $order = $args->header[$orderField]->field." ".$dir;
        
        $where = array("1");

        foreach ($this->request->post AS $k => $v) {
            $k = explode("_", $k);
            if (!isset($k[1])) continue;
            if ($k[0] == "sSearch" && $v != "") {
                $v = \Utils::escape($v);
                $key = "`".$args->header[$k[1]]->field."`";
                if ($args->header[$k[1]]->type == "date") {
                    $key = "DATE_FORMAT(`".$args->header[$k[1]]->field."`, '%e.%m.%Y')";
                }
                if ($args->header[$k[1]]->type == "datetime") {
                    $key = "DATE_FORMAT(`".$args->header[$k[1]]->field."`, '%e.%m.%Y %H:%i:%s')";
                }
                if ($args->header[$k[1]]->type == "callback") {
                    
                }
                $where[] = $key." LIKE '".$v."%'";
            }
        }
        
        if ($args->table) {
            $all = $data = $this->query($args->table)->where(implode(" AND ", $where))->run();
            $num = $this->db->num_rows($all);

            $data = $this->query($args->table)->where(implode(" AND ", $where))->limit($count)->start($start)->order($order)->asArray()->run();
        } else if ($args->callback) {
            $host = "http://nreins.de/";
            if (stristr($_SERVER["HTTP_HOST"], "preview")) $host = "http://nreins:wahox@preview.nreins.de/";
            if (stristr($_SERVER["HTTP_HOST"], "test")) $host = "http://test.nreins.de/";
            if ($_SERVER["HTTP_HOST"] == "admin.nreins" || $_SERVER["HTTP_HOST"] == "nreins") $host = "http://test.nreins.de/";

            $url = $host.$args->callback;
            $post = $this->request->post;
            $post["where"] = $where;
            $post["start"] = $start;
            $post["count"] = $count;
            $post["order"] = $order;
            $post["key"] = "gvbntirgfvbnrgobvwgborzgvzbrbgzoogbwztvongzngznovgerntzgnrezwtngrezt6tvnw34";
            $s = new \Snoopy();
            $s->submit($url, $post);

            $result = json_decode($s->results);
            $num = $result->num;
            $data = $result->data;
        }
        return array($num, $data);
    }

    /**
     * Diese Funktion baut das Datenarray zusammen und formatiert die Felder nach dem Datentyp.
     * @param Array $data Ergebnis einer DB-Anfrage ($this->query(xx)->[..]->asArray()->run()
     * @param Array $head Der in indexAction übergebene Header ($args->header)
     * @return Array mit den Daten
     */
    private function buildData($data, $head) {
        print_r($head);
        $result = array();
        foreach($data AS $row) {
            $row = (array) $row;
            $cols = array();
            reset($head);
            foreach($head AS $h) {
                $content = "";
                if ($h->field) {
                    $deleted = false;
                    if (isset($row["sichtbar"]) && $row["sichtbar"] == 0) $deleted = true;
                    if (isset($row["deleted"]) && $row["deleted"] == 1) $deleted = true;
                    $content = $row[$h->field];
                    $this->format($content, $h->type, $deleted,$h->source);
                } 
                if ($h->html) {
                    $content = $this->parseHtml($h->html, $row);
                }

                $cols[] = $content;
            }

            if (
                (isset($row["deleted"]) && $row["deleted"] == 1) ||
				(isset($row["sichtbar"]) && $row["sichtbar"] == 0)
            ) {
                $tplRow->STYLE = "style='opacity:0.5; text-decoration:line-through;'";
            }
            $result[] = $cols;
        }
        return $result;
    }

    /**
     * Formatiert einen Datensatz
     * @param String $content Rohdaten
     * @param String $type Datentyp
     */
    private function format(&$content, $type, $deleted=false, $source=null) {
        switch ($type) {
            case "int":
                $content = intval($content);
                break;
            case "date":
                $date = new \DateTime($content);
                $content = "<span style='display:none;'>".$date->format("Y-m-d")."</span>".$date->format("d.m.Y");
                break;
            case "datetime":
                $date = new \DateTime($content);
                $content = "<span style='display:none;'>".$date->format("Y-m-d H:i:s")."</span>".$date->format("d.m.Y H:i:s");
                break;
            case "callback":
                $s = explode(":", $source);
                $table = ucfirst($s[0]);
                $field = $s[1];
                $classname = "\Datacontainer\\".$table;
                $data = new $classname($content);
                $content .= " - ".$data->$field;
                break;
            default :
                if (stristr($type, "float")) {
                    $t = explode(":", $type);
                    $content = number_format(floatval($content), intval($t[1]), ",", ".");
                }
                break;
        }
        if ($deleted) $content = "<span style='opacity:0.5; text-decoration:line-through;'>".$content."</span>";
    }
    
    private function parseHtml($html, $data) {
        $c = new \Controller();
        $c->setTemplate($html);
        foreach($data AS $key => $value) {
            $placeholder = strtoupper($key);
            $c->$placeholder = $value;
        }
        return $c->render();
        
    }
    
}

?>
