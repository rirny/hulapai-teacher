<?php
/**
 * 注册
 */
class RegisterController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		if($this->_POST){
			$account = $this->post('account','','trim');		
			$password = $this->post('password','','trim');
			$repassword = $this->post('repassword','','trim');
			$verify = $this->post('verify','','trim');
			if(!$account) show_message('用户名不能为空！');
			if(!$password) show_message('密码不能为空！');
			if(!$repassword) show_message('确认密码不能为空！');
			if($password != $repassword) show_message('两次密码必须一致！');
			if(!$verify) show_message('验证码不能为空！');
			$_User = new UserModel();		
			if($_User->getRow(array('account' => $account))) show_message('用户已存在，如已忘记密码，请通过忘记密码找回!');
	        $_Verify = new VerifyModel();
	        if(!$_Verify->verify($account, $verify)) {
	            show_message('验证码不正确！');
	        }
			$login_salt = rand(10000,99999);
			$password = md5(md5($password) . $login_salt);
			$data = array(
				'account'=>$account,
				'password'=>$password,
				'login_salt'=>$login_salt,
				'create_time'=>time(),
				'setting'=>json_encode(array(
					"hulaid" => 0,
	                "friend_verify" => 1,
	                "notice" => array(
	                    "method" => 0,
	                    "types" => "1,2,3,4,5"
					)
				)),
				'last_login_time'=>time(),
				'last_login_ip'=>getIp(),
			);
			$id = $_User->register($data, $verify);
			if(!$id)	show_message('注册失败！');
			$useInfo = $_User->getRow(array('id'=>$id),'id as uid,account,nickname,hulaid');
			Yaf_Session::getInstance()->set('user',$useInfo);
			$this->redirect('/User');	
		}
	}
}
