<?php
/**
 * msgtype
 * desc  异常
 *  end
 */
class ErrorController extends Yaf_Controller_Base_Abstract {
	public function indexAction(){}
	public function errorAction($exception) {
		$this->getView()->setScriptPath(APPLICATION_PATH ."/application/views");
		switch($exception->getCode()) {
	      case YAF_ERR_NOTFOUND_MODULE:
	      case YAF_ERR_NOTFOUND_CONTROLLER:
	      case YAF_ERR_NOTFOUND_ACTION:
	    	$this->getView()->assign('code', 404);
	      break;
	      default:
	      break;
	  	}
	  	$this->getView()->assign('message', $exception->getMessage());
	  	$this->getView()->assign('url_forward', url());
	}
}