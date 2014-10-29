<?php
/**
 * 登出
 */
class LogoutController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		if($this->user){
			Yaf_Session::getInstance()->del('user');
		}
		show_message('退出成功！','/Login');
	}
}
