<?php
/**
 * 班级
 */
class GradeController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		$page = $this->get('page',1,'intval');
		$_Grade = new GradeModel();
		$grades = $_Grade->getList($page,20,array('school'=>$this->school));
		if($grades['data']){
			$_GradeStudent = new Grade_StudentModel();
			foreach($grades['data'] as &$grade){
				$grade['num'] = $_GradeStudent->getCount(array('school'=>$this->school,'grade'=>$grade['id']));
			}
		}
		$this->getView()->assign('school', $this->school);
		$this->getView()->assign('pages', $grades['pages']);
		$this->getView()->assign('grades', $grades['data']);
	}
	
	public function addAction(){
		if($this->_POST){
			$name = $this->post('name','','trim');
			if(!$name) show_message('参数错误！');
			$_Grade = new GradeModel();
			if($_Grade->getRow(array('school'=>$this->school,'name'=>$name)))   show_message('班级名已存在！');
			$data = array(
				'name'=>$name,
				'school'=>$this->school,
				'creator'=>$this->uid,
				'create_time'=>time(),
			);
			if(!$_Grade->insertData($data))    show_message('班级添加失败！');
			show_message('班级添加成功！','','add');
		}	
	}
	
	public function editAction(){
		if($this->_POST){
			$id = $this->post('id',0,'intval');
			$name = $this->post('name','','trim');
			if(!$name) show_message('参数错误！');
			$_Grade = new GradeModel();
			$grade = $_Grade->getRow(array('school'=>$this->school,'id'=>$id));
			if(!$grade)  show_message('班级不存在！');
			if($_Grade->getRow(array('school'=>$this->school,'name'=>$name)))   show_message('班级名已存在！');
			$data = array(
				'name'=>$name
			);
			if(!$_Grade->updateData($data,array('school'=>$this->school,'id'=>$id)))    show_message('班级修改失败！');
			show_message('班级修改成功！','','edit');
		}else{
			$id = $this->get('id',0,'intval');
			if(!$id) show_message('参数错误！');
			$_Grade = new GradeModel();
			$grade = $_Grade->getRow(array('school'=>$this->school,'id'=>$id));
			if(!$grade)   show_message('班级不存在！');
			$this->getView()->assign('grade', $grade);
		}	
	}
	
	
	public function deleteAction(){
		$id = $this->get('id',0,'intval');
		if(!$id) show_message('参数错误！');
		$_Grade = new GradeModel();
		$grade = $_Grade->getRow(array('school'=>$this->school,'id'=>$id));
		if(!$grade) show_message('班级不存在！');
		if(!$_Grade->deleteGrade($this->school,$id)) show_message('删除失败！');
		show_message('删除成功！',url('school','grade'));
	}
}
