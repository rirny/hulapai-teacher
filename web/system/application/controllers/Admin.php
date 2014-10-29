<?php
/**
 * 后台
 */
class AdminController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		$this->checkLogin();
		$id = 1;
		$_AdminUser = new Admin_UserModel();
		$userInfo = $_AdminUser->getRow(array('gid'=>$id,'uid'=>$this->uid));
		if(!$userInfo || $userInfo['type'] != "admin") show_message('没有权限！');
		$url = '';
		$this->user['enable'] = $userInfo['enable'];
		$this->user['gid'] = $userInfo['gid'];
		$this->user['school'] = $userInfo['school'];
		if($userInfo['type'] == "admin"){
			$url = url('admin');
			$this->user['module'] = "Admin";
		}
		if(!$url) show_message('url不存在！');
		Yaf_Session::getInstance()->set('user',$this->user);
		$this->redirect($url);
	}
}