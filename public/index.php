<?php
set_include_path(".:../framework");

$start = microtime(true); 
ob_start();
require('lib/core/init.php');

$request = Request::getInstance();
$app = Application::getInstance();
$app->setStartTime($start);

require_once("master/Master.php");
$master = new Master("master/_tpl.html");
if ($request->ajax) $master->disableRender();
$master->indexAction();

Application::getInstance()->render($master);