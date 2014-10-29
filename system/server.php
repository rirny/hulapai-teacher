<?php
define('LOGS_PATH', '/data/www/logs');
define('APPLICATION_PATH', dirname(__FILE__));
$app = new Yaf_Application( APPLICATION_PATH . "/conf/application.ini");
$arrConfig = Yaf_Application::app()->getConfig();
Yaf_Registry::set('config', $arrConfig);
//加载基本类库
Yaf_Loader::import(APPLICATION_PATH.'/application/library/DB/Driver.php');
Yaf_Loader::import(APPLICATION_PATH.'/application/library/DB/Result.php');
Yaf_Loader::import(APPLICATION_PATH.'/application/library/DB/ActiveRecord.php');
Yaf_Loader::import(APPLICATION_PATH.'/application/library/DB.php');
Yaf_Loader::import(APPLICATION_PATH.'/application/library/DB/Mysqli/Result.php');
Yaf_Loader::import(APPLICATION_PATH.'/application/library/DB/Mysqli/Driver.php');
//加载公用方法
Yaf_Loader::import(APPLICATION_PATH.'/application/library/Base/Cache.php');
Yaf_Loader::import(APPLICATION_PATH.'/application/library/Base/Fun.php');

Yaf_Loader::import(APPLICATION_PATH.'/application/library/Task.php');

ini_set('magic_quotes_gpc','off');
ini_set('date.timezone','Asia/Shanghai');
ini_set('display_errors','on');
error_reporting(E_ALL ^ E_NOTICE);
set_time_limit(0);
ini_set('memory_limit','100M');