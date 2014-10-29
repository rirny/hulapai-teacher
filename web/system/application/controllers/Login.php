<?php
/**
 * 登录
 */
class LoginController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		$redirect = DOMAIN == "common" ? "/User" : "/".ucfirst(DOMAIN);
		if(!$this->user){
			if($this->_POST){
				$username = $this->_POST['username'];
				$password = $this->_POST['password'];
				if(!$username) show_message('用户名不能为空！');
				if(!$password) show_message('密码不能为空！');
				$_User = new UserModel();
				$useInfo = $_User->getRow("account = '$username' or hulaid = '$username'",'id as uid,account,firstname,lastname,nickname,password,hulaid,login_salt,teacher');
				if(!$useInfo) show_message('用户不存在！');
				if($useInfo['password'] !== md5(md5($password) . $useInfo['login_salt'])) show_message('密码不正确！');
				unset($useInfo['password']);
				unset($useInfo['login_salt']);
				Yaf_Session::getInstance()->set('user',$useInfo);
				$this->redirect($redirect);
			}
		}else{
			$this->redirect($redirect);
		}
	}
}
