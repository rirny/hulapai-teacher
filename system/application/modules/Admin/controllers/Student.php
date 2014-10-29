<?php
/**
 * 学生
 */
class StudentController extends Yaf_Controller_Base_Abstract {
	/**
	 * 列表
	 */
	public function indexAction(){
		$page = $this->get('page',1,'intval');
		$studentName = $this->get('studentName','','trim');
		$studentName = $studentName != "学生名" ? $studentName:'';
		$status = $this->get('status',-1,'intval');
		$_Student = new StudentModel();
		$students = $_Student->getStudentList($page,20,$studentName,$status);
		$this->getView()->assign('pages', $students['pages']);
		$this->getView()->assign('students', $students['data']);
	}
	
	
	public function infoAction(){
		$student = $this->get('student',0,'intval');
		if(!$student) show_message('参数错误！');
		//用户是否学生
		$_Student = new StudentModel();
		$studentInfo = $_Student->getRow(array('id'=>$student),'id,name,avatar,tag,birthday,parent_name,phone,source');
		if(!$studentInfo) show_message('用户非学生！');	
		$parents = array();
		//获取学生家长
		if($studentInfo['source'] != 1){
			$_UserStudent = new User_StudentModel();
			$parents = $_UserStudent->getAll(array('student'=>$student));
			if($parents){
				$_User = new UserModel();
				foreach($parents as &$parent){
					$info = $_User->getRow(array('id'=>$parent['user']),'hulaid,nickname,account');
					$parent['nickname'] = $info['nickname'];
					$parent['hulaid'] = $info['hulaid'];
					$parent['account'] = $info['account'];
				}
			}
		}else{
			$parents[] = array(
				'user'=>0,
				'student'=>$student,
				'relation'=>4,
				'nickname'=>$studentInfo['parent_name'],
				'account'=>$studentInfo['phone']
			);
		}
		$this->getView()->assign('student',$studentInfo);
		$this->getView()->assign('parents',$parents);
	}
}