<?php
/**
 * 老师
 */
class TeacherController extends Yaf_Controller_Base_Abstract {
	/**
	 * 列表
	 */
	public function indexAction(){
		$page = $this->get('page',1,'intval');
		$teacherName = $this->get('teacherName','','trim');
		$teacherName = $teacherName != "老师名" ? $teacherName:'';
		$status = $this->get('status',-1,'intval');
		$_Teacher = new TeacherModel();
		$teachers = $_Teacher->getTeacherList($page,20,$teacherName,$status);
		$this->getView()->assign('pages', $teachers['pages']);
		$this->getView()->assign('teachers', $teachers['data']);
	}
	
	public function infoAction(){
		$teacher = $this->get('teacher',0,'intval');
		if(!$teacher) show_message('参数错误！');
		//用户是否老师
		$_Teacher = new TeacherModel();
		$teacherInfo = $_Teacher->getRow(array('user'=>$teacher));
		if(!$teacherInfo) show_message('用户非教师！');
		$_User = new UserModel();
		$userInfo = $_User->getRow(array('id'=>$teacher));
		$teacherInfo['userInfo'] = $userInfo;
		$this->getView()->assign('teacher',$teacherInfo);
	}
}