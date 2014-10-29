<?php
define('LOGS_PATH', '/data/www/logs');
define('APPLICATION_PATH', dirname(__FILE__));
define('API_PATH', dirname(dirname(APPLICATION_PATH)).'/api');
define('CMS_HTML', dirname(APPLICATION_PATH).'/cms/html');
define('UPLOAD_PATH', dirname(dirname(APPLICATION_PATH)).'/upload');

//是否开启了2级域名
$hosts = explode('.',$_SERVER['HTTP_HOST']);
define('DOMAIN', count($hosts) == 3 && in_array(strtolower($hosts[0]),array('student','teacher','school','admin'))? strtolower($hosts[0]) : "common");
$application = new Yaf_Application( APPLICATION_PATH . "/conf/application.ini",DOMAIN);
$arrConfig = Yaf_Application::app()->getConfig();
Yaf_Registry::set('config', $arrConfig);
$application->bootstrap()->run();
?>