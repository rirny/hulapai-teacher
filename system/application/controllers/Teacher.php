<?php
/**
 * 老师
 */
class TeacherController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		$this->checkLogin();
		$this->user['module'] = "Teacher";
		Yaf_Session::getInstance()->set('user',$this->user);
		$this->redirect(url('teacher','index','index'));
		
	}
}