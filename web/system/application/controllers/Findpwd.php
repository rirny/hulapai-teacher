<?php
/**
 * 找回密码
 */
class FindpwdController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		if($this->_POST){
			$account = $this->post('account','','trim');		
			$password = $this->post('password','','trim');
			$repassword = $this->post('repassword','','trim');
			$verify = $this->post('verify','','trim');
			if(!$account) show_message('用户名不能为空！');
			if(!$password) show_message('新密码不能为空！');
			if(!$repassword) show_message('确认密码不能为空！');
			if($password != $repassword) show_message('两次密码必须一致！');
			if(!$verify) show_message('验证码不能为空！');
			$_User = new UserModel();
			$userInfo = $_User->getRow(array('account' => $account));		
			if(!$userInfo) show_message('用户不存在!');
	        $_Verify = new VerifyModel();
	        if(!$_Verify->verify($account, $verify,1)) {
	            show_message('验证码不正确！');
	        }
			$password = md5(md5($password) . $userInfo['login_salt']);
			$data = array(
				'password'=>$password,
			);
			if(!$_User->findPwd($userInfo['account'],$data, $verify))	show_message('密码修改失败！');
			show_message('密码修改成功！','/User');
		}
	}
}
