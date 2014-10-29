<?php
/**
 * 用户中心
 */
class UserController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		
	}
	
	public function infoAction() {
		$this->checkLogin();
		$_User = new UserModel();	
		$userInfo = $_User->getRow(array('id' => $this->uid));	
		if(!$userInfo) show_message('用户不存在！');
		$userInfo['setting'] = json_decode($userInfo['setting'],true);
		if($this->_POST){
			$hulaid = $this->post('hulaid','','trim');
			if($hulaid && $userInfo['setting']['hulaid']) show_message('呼啦号只能修改一次！!');
			$nickname = $this->post('nickname','','trim');
			$province = $this->post('province',0,'intval');
			$city = $this->post('city',0,'intval');
			$area = $this->post('area',0,'intval');
//			if(!$nickname) show_message('昵称不能为空!');
//			if(!$province) show_message('省份不能为空!');
//			if(!$city) show_message('城市不能为空!');
//			if(!$area) show_message('地区不能为空!');
			$data = array(
				'nickname'=>$nickname,
				'nickname_en'=>Ustring::topinyin($nickname),
				'birthday'=>$this->post('birthday','','isDate'),
				'province'=>$province,
				'city'=>$city,
				'area'=>$area,
				'address'=>$this->post('address','','trim'),
				'sign'=>$this->post('sign','','trim'),
			);
			if($hulaid && $hulaid != $userInfo['hulaid']){
				$data['hulaid'] = $hulaid;
				$userInfo['setting']['hulaid'] = 1;
				$data['setting'] = json_encode($userInfo['setting']);
			}
			if(!$_User->updateData($data,array('id'=>$this->uid))) show_message('修改失败！');
			show_message('修改成功！');
		}
		$timestamp = time();
		$this->getView()->assign('timestamp', $timestamp);
		$this->getView()->assign('token', md5(Yaf_Registry::get('config')->path->apiKey.$timestamp));
		$this->getView()->assign('id', $this->uid);
		$this->getView()->assign('userInfo', $userInfo);
	}
	
	public function editPwdAction() {
		$this->checkLogin();
		$_User = new UserModel();	
		$userInfo = $_User->getRow(array('id' => $this->uid));	
		if(!$userInfo) show_message('用户不存在!');
		if($this->_POST){	
			$oldpassword = $this->post('oldpassword','','trim');
			$password = $this->post('password','','trim');
			$repassword = $this->post('repassword','','trim');
			if(!$oldpassword) show_message('旧密码不能为空！');
			if($userInfo['password'] != md5(md5($oldpassword) . $userInfo['login_salt'])) show_message('旧密码错误！');
			if(!$password) show_message('新密码不能为空！');
			if(!$repassword) show_message('确认密码不能为空！');
			if($password != $repassword) show_message('两次密码必须一致！');
			if($password == $oldpassword) show_message('新旧密码不能一致！');
			$password = md5(md5($password) . $userInfo['login_salt']);
			if(!$_User->updateData(array('password'=>$password),array('id'=>$this->uid))) show_message('修改密码失败!');
			show_message('修改密码成功!',url('index','user','info'));
		}
	}
	
	public function avatarAction() {
		$this->checkLogin();
		$timestamp = time();
		$this->getView()->assign('timestamp', $timestamp);
		$this->getView()->assign('token', md5(Yaf_Registry::get('config')->path->apiKey.$timestamp));
		$this->getView()->assign('id', $this->uid);
	}
}
