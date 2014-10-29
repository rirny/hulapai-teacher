<?php
/**
 * 分组老师
 */
class Group_TeacherController extends Yaf_Controller_Base_Abstract {
	/**
	 * 列表
	 */
	public function indexAction(){
		$page = $this->get('page',1,'intval');
		$id = $this->get('id',0,'intval');
		if(!$id) show_message('参数错误！');
		$_SchoolGroup = new School_GroupModel();
		$group = $_SchoolGroup->getRow(array('school'=>$this->school,'id'=>$id));
		if(!$group)  show_message('分组不存在！');
		//取得分组老师
		$_SchoolGroupTeacher = new School_Group_TeacherModel();
		$teachers = $_SchoolGroupTeacher->getList($page,20,array('school'=>$this->school,'group'=>$id));
		if($teachers['data']){
			$_User = new UserModel();
			foreach($teachers['data'] as &$teacher){
				$userInfo = $_User->getRow(array('id'=>$teacher['teacher']),'firstname,lastname');
				$teacher['userInfo'] = $userInfo;
			}
		}
		$this->getView()->assign('id', $id);
		$this->getView()->assign('pages', $teachers['pages']);
		$this->getView()->assign('teachers', $teachers['data']);
	}
	
	public function addAction(){
		if($this->_POST){
			$id = $this->post('id',0,'intval');
			$teachers = $this->post('teacher');
			if(!$id || !$teachers) show_message('参数错误！');
			$_SchoolGroup = new School_GroupModel();
			$group = $_SchoolGroup->getRow(array('school'=>$this->school,'id'=>$id));
			if(!$group)  show_message('分组不存在！');
			$_SchoolGroupTeacher = new School_Group_TeacherModel();
			$data = array(
				'school'=>$this->school,
				'group'=>$id,
			);
			foreach($teachers as $teacher){
				if(!$_SchoolGroupTeacher->getRow(array('school'=>$this->school,'group'=>$id,'teacher'=>$teacher))){
					$data['teacher'] = $teacher;
					$_SchoolGroupTeacher->insertData($data);
				}
			}
			show_message('老师添加成功！','','add');
		}else{
			$id = $this->get('id',0,'intval');
			$_SchoolGroup = new School_GroupModel();
			$group = $_SchoolGroup->getRow(array('school'=>$this->school,'id'=>$id));
			if(!$group)  show_message('分组不存在！');
			//获取机构下的老师
			$_SchoolTeacher = new School_TeacherModel();
			$teachers = $_SchoolTeacher->getAll(array('school'=>$this->school,'status'=>0));
			//取得分组老师
			$_SchoolGroupTeacher = new School_Group_TeacherModel();
			$group_teachers = $_SchoolGroupTeacher->getAll(array('school'=>$this->school,'group'=>$id));
			$group_teacherIds = array();
			if($group_teachers){
				foreach($group_teachers as &$group_teacher){
					$group_teacherIds[] = $group_teacher['teacher'];
				}
			}
			if($teachers){
				$_User = new UserModel();
				foreach($teachers as &$teacher){
					$userInfo = $_User->getRow(array('id'=>$teacher['teacher']),'firstname,lastname');
					$teacher['userInfo'] = $userInfo;
					$teacher['in'] = 0;
					if(in_array($teacher['teacher'],$group_teacherIds)) $teacher['in'] = 1;
				}
			}
			$this->getView()->assign('id', $id);
			$this->getView()->assign('teachers', $teachers);
		}
	}
	
	public function deleteAction(){
		$id = 0;
		$teachers = array();
		if($this->_POST){
			$id = $this->post('id',0,'intval');
			$teachers = $this->post('teacher');
		}elseif($this->_GET){
			$id = $this->get('id',0,'intval');
			$teacher = $this->get('teacher','0','intval');
			$teachers = array($teacher);
		}
		if(!$id || !$teachers) show_message('参数错误！');
		$_SchoolGroup = new School_GroupModel();
		$group = $_SchoolGroup->getRow(array('school'=>$this->school,'id'=>$id));
		if(!$group) show_message('分组不存在！');
		$teachers = implode(',',$teachers);
		$_SchoolGroupTeacher = new School_Group_TeacherModel();
		if(!$_SchoolGroupTeacher->deleteData("school = $this->school and `group` = $id and teacher in ($teachers)")) show_message('删除失败！');
		show_message('删除成功！',url('school','group_teacher','index','id='.$id));
	}
}