<?php
/**
 * 首页
 */
class IndexController extends Yaf_Controller_Base_Abstract {
	/**
	 * 首页
	 */
	public function indexAction() {
		$this->redirect(url('teacher','info'));	
	}
	
	public function selectSchoolAction(){
		$id = $this->get('school',0,'intval');
		if($id){
			$_School_Teacher = new School_TeacherModel();
			if(!$_School_Teacher->getRow(array("teacher"=>$this->tid,'school'=>$id))) exit("0");
		}
		if($id == $this->school) exit("0");
		$this->user['school'] = $id;
		Yaf_Session::getInstance()->set('user',$this->user);
		exit("1");
	}
	
	public function addSchoolAction(){
		$code = $this->post('code','','trim');
		if($code){
			$_School = new SchoolModel();
			$schoolInfo = $_School->getRow("code = '$code'");
			if(!$schoolInfo) show_message('机构号不存在！');
			//是否已经加入
			$_School_Teacher = new School_TeacherModel();
			if($_School_Teacher->getRow(array('school'=>$schoolInfo['id'],'teacher'=>$this->tid))) show_message('您已加入该机构！');
			//是否已经发送申请
			$_Apply = new ApplyModel();
			if($_Apply->getApplyForTeacherAddSchool($this->uid,$schoolInfo['id']))  show_message('已经发送过申请了，不能重复发送！');
			$id = $_Apply->sendApplyForTeacherAddSchool($this->uid,$schoolInfo['id']);
			if(!$id) show_message('申请发送失败！');
			show_message('申请发送成功！','','school_add');
		}
	}
}
