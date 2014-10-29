<?php
/**
 * @name Bootstrap
 * @author root
 * @desc 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * @see http://www.php.net/manual/en/class.yaf-bootstrap-abstract.php
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
class Bootstrap extends Yaf_Bootstrap_Abstract{
	/**
	 * 把配置保存起来
	 */
	public function _initConfig() {
		ini_set('magic_quotes_gpc','off');
		ini_set('date.timezone','Asia/Shanghai');
		if(Yaf_Registry::get('config')->project->mode){
			ini_set('display_errors','on');
			error_reporting(E_ALL ^ E_NOTICE);
			apc_clear_cache();
			apc_clear_cache('user');
		}else{
			ini_set('display_errors','off');
			error_reporting(0);
		}
	}
	
	/**
	 * session_start
	 */
	public function _initSession(){
		$sessionCfg = Yaf_Registry::get('config')->session->toArray();
		ini_set('session.cookie_domain',$sessionCfg['domain']);
		session_name($sessionCfg['name']);
		isset($_COOKIE[$sessionCfg['name']]) && session_id($_COOKIE[$sessionCfg['name']]);
		Yaf_Session::getInstance()->start();
		header("Content-type: text/html; charset=utf-8");
		header("cache-control:no-cache,must-revalidate");
	}
	
	/**
	 * 加载公用类库
	 */
	public function _initBase(Yaf_Dispatcher $dispatcher){
		Yaf_Loader::import('Base.php');
	}
	
	/**
	 * 注册一个插件
	 */
	public function _initPlugin(Yaf_Dispatcher $dispatcher) {
		$objHookPlugin = new HookPlugin();
		$dispatcher->registerPlugin($objHookPlugin);
	}
	
	/**
	 * 注册路由
	 */
	public function _initRoute(Yaf_Dispatcher $dispatcher) {
		/*
		$router = $dispatcher->getRouter();
        $router->addConfig(Yaf_Registry::get("config")->routes);
      	*/
	}
	
	/**
	 * 丰富原有视图方法
	 */
	public function _initView(Yaf_Dispatcher $dispatcher){
		$view= new Smarty_Adapter(null, Yaf_Registry::get("config")->get("smarty"));
    	Yaf_Dispatcher::getInstance()->setView($view);
	}
}
