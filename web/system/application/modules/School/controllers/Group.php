<?php
/**
 * 教师组
 */
class GroupController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		$page = $this->get('page',1,'intval');
		$_SchoolGroup = new School_GroupModel();
		$groups = $_SchoolGroup->getList($page,20,array('school'=>$this->school));
		if($groups['data']){
			$_SchoolGroupTeacher = new School_Group_TeacherModel();
			foreach($groups['data'] as &$group){
				$group['num'] = $_SchoolGroupTeacher->getCount(array('school'=>$this->school,'group'=>$group['id']));
			}
		}
		$this->getView()->assign('school', $this->school);
		$this->getView()->assign('pages', $groups['pages']);
		$this->getView()->assign('groups', $groups['data']);
	}
	
	public function addAction(){
		if($this->_POST){
			$name = $this->post('name','','trim');
			if(!$name) show_message('参数错误！');
			$_SchoolGroup = new School_GroupModel();
			if($_SchoolGroup->getRow(array('school'=>$this->school,'name'=>$name)))   show_message('分组名已存在！');
			$data = array(
				'name'=>$name,
				'school'=>$this->school,
				'creator'=>$this->uid,
				'create_time'=>time(),
			);
			if(!$_SchoolGroup->insertData($data))    show_message('分组添加失败！');
			show_message('分组添加成功！','','add');
		}	
	}
	
	public function editAction(){
		if($this->_POST){
			$id = $this->post('id',0,'intval');
			$name = $this->post('name','','trim');
			if(!$name) show_message('参数错误！');
			$_SchoolGroup = new School_GroupModel();
			$group = $_SchoolGroup->getRow(array('school'=>$this->school,'id'=>$id));
			if(!$group)  show_message('分组不存在！');
			if($_SchoolGroup->getRow(array('school'=>$this->school,'name'=>$name)))   show_message('分组名已存在！');
			$data = array(
				'name'=>$name
			);
			if(!$_SchoolGroup->updateData($data,array('school'=>$this->school,'id'=>$id)))    show_message('分类修改失败！');
			show_message('分类修改成功！','','edit');
		}else{
			$id = $this->get('id',0,'intval');
			if(!$id) show_message('参数错误！');
			$_SchoolGroup = new School_GroupModel();
			$group = $_SchoolGroup->getRow(array('school'=>$this->school,'id'=>$id));
			if(!$group)   show_message('分组不存在！');
			$this->getView()->assign('group', $group);
		}	
	}
	
	
	public function deleteAction(){
		$id = $this->get('id',0,'intval');
		if(!$id) show_message('参数错误！');
		$_SchoolGroup = new School_GroupModel();
		$group = $_SchoolGroup->getRow(array('school'=>$this->school,'id'=>$id));
		if(!$group) show_message('分组不存在！');
		if(!$_SchoolGroup->deleteGroup($this->school,$id)) show_message('删除失败！');
		show_message('删除成功！',url('school','group'));
	}
}
