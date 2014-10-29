<?php
/**
 * 命令行执行  php cmd.php Pizza getPizza 'a=1&b=2'
 */
$controller = $_SERVER['argv'][1]? $_SERVER['argv'][1] : 'Index';
$action = $_SERVER['argv'][2]? $_SERVER['argv'][2] : 'Index';
$param = array();
if(isset($_SERVER['argv'][3])){
	parse_str($_SERVER['argv'][3], $param);
	$param = is_array($param) ? $param : array();
}
define('APPLICATION_PATH', dirname(__FILE__));
$app = new Yaf_Application( APPLICATION_PATH . "/conf/application.ini");
$arrConfig = Yaf_Application::app()->getConfig();
Yaf_Registry::set('config', $arrConfig);
$app->bootstrap()->getDispatcher()->dispatch(new Yaf_Request_Simple("CLI","Index", $controller, $action, $param));
?>